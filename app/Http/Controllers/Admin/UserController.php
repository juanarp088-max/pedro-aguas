<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::with('roles')->get(),
            'roles' => Role::all(),
        ]);
    }

    public function store(Request $request)
    {
        // ✅ Validación para CREAR - password OBLIGATORIO
        $request->validate([
            'name'     => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|exists:roles,name'
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role.required' => 'Debe seleccionar un rol.',
            'role.exists' => 'El rol seleccionado no existe.'
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole($request->role);

            DB::commit();

            Log::info('Usuario creado exitosamente', [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $request->role,
                'usuario' => Auth::id()
            ]);

            return back()->with('success', 'Usuario creado con éxito.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear usuario: ' . $e->getMessage(), [
                'data' => $request->all(),
                'usuario' => Auth::id()
            ]);
            return back()->withErrors(['error' => 'Error al crear el usuario: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        // ✅ Validación para ACTUALIZAR - password OPCIONAL
        $request->validate([
            'name'     => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'email'    => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|min:8|confirmed', // ← nullable = opcional
            'role'     => 'required|exists:roles,name'
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role.required' => 'Debe seleccionar un rol.',
            'role.exists' => 'El rol seleccionado no existe.'
        ]);

        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);
            
            if ($user->id === Auth::id() && $request->role !== 'admin') {
                DB::rollBack();
                return back()->withErrors(['error' => 'No puedes cambiar tu propio rol.']);
            }

            $data = [
                'name'  => $request->name,
                'email' => $request->email,
            ];

            // ✅ SOLO actualizar contraseña si se envió
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);
            $user->syncRoles([$request->role]);

            DB::commit();

            Log::info('Usuario actualizado exitosamente', [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $request->role,
                'usuario' => Auth::id()
            ]);

            return back()->with('success', 'Usuario actualizado con éxito.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar usuario', [
                'id' => $id,
                'error' => $e->getMessage(),
                'usuario' => Auth::id()
            ]);
            return back()->withErrors(['error' => 'Error al actualizar el usuario: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);
            
            if ($user->id === Auth::id()) {
                DB::rollBack();
                return back()->withErrors(['error' => 'No puedes eliminar tu propio usuario.']);
            }

            $adminCount = User::role('admin')->count();
            if ($user->hasRole('admin') && $adminCount <= 1) {
                DB::rollBack();
                return back()->withErrors(['error' => 'No puedes eliminar al último administrador.']);
            }

            $user->syncRoles([]);
            $user->delete();

            DB::commit();

            Log::info('Usuario eliminado exitosamente', [
                'id' => $id,
                'email' => $user->email,
                'eliminado_por' => Auth::id()
            ]);

            return back()->with('success', 'Usuario eliminado con éxito.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar usuario', [
                'id' => $id,
                'error' => $e->getMessage(),
                'usuario' => Auth::id()
            ]);
            return back()->withErrors(['error' => 'Error al eliminar el usuario: ' . $e->getMessage()]);
        }
    }
}