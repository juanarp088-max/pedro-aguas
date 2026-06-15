<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pregunta;
use App\Models\OpcionPregunta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;  // ← Importante


class PreguntaController extends Controller
{
    /**
     * Reglas de validación
     */
    protected function getReglasValidacion()
    {
        return [
            'descripcion' => 'required|string|max:500',
            'tipo' => 'required|in:simple,multiple',
            'activa' => 'boolean',
            'opciones' => 'required_if:tipo,multiple|array',
            'opciones.*.opcion' => 'required_if:tipo,multiple|string|max:255',
            'opciones.*.requiere_especificar' => 'boolean',
        ];
    }

    /**
     * Mensajes personalizados
     */
    private function getMensajesPersonalizados()
    {
        return [
            'descripcion.required' => 'La pregunta es obligatoria.',
            'descripcion.string' => 'La pregunta debe ser texto válido.',
            'descripcion.max' => 'La pregunta no puede exceder los 500 caracteres.',
            'descripcion.min' => 'La pregunta debe tener al menos 5 caracteres.',
            'activa.boolean' => 'El estado debe ser activo o inactivo.',
            'tipo.required' => 'Debe seleccionar el tipo de pregunta.',
            'tipo.in' => 'El tipo de pregunta no es válido.',
            'opciones.required_if' => 'Debe agregar al menos una opción para preguntas compuestas.',
            'opciones.*.opcion.required_if' => 'Todas las opciones deben tener un texto.',
        ];
    }

    /**
     * Formatear pregunta a mayúsculas con signos de interrogación
     */
    private function formatearPregunta($descripcion)
    {
        $descripcion = preg_replace('/\s+/', ' ', trim($descripcion));
        $descripcion = mb_strtoupper($descripcion, 'UTF-8');
        $descripcion = str_replace(['?', '¿'], '', $descripcion);
        $descripcion = '¿' . trim($descripcion) . '?';
        return $descripcion;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $preguntas = Pregunta::with('opciones')->orderBy('id', 'asc')->get();

            return Inertia::render('preguntas/index', [
                'preguntas' => $preguntas
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cargar preguntas: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al cargar las preguntas']);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('preguntas/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->getReglasValidacion(),
            $this->getMensajesPersonalizados()
        );

        DB::beginTransaction();
        
        try {
            $descripcionFormateada = $this->formatearPregunta($validated['descripcion']);
            
            $existente = Pregunta::where('descripcion', $descripcionFormateada)->first();
            if ($existente) {
                DB::rollBack();
                return back()->withErrors([
                    'descripcion' => 'Ya existe una pregunta con este texto.'
                ])->withInput();
            }
            
            $pregunta = Pregunta::create([
                'descripcion' => $descripcionFormateada,
                'activa' => $validated['activa'] ?? true,
                'tipo' => $validated['tipo'],
            ]);

            // Si es pregunta compuesta, guardar opciones
            if ($validated['tipo'] === 'multiple' && !empty($validated['opciones'])) {
                foreach ($validated['opciones'] as $index => $opcion) {
                    OpcionPregunta::create([
                        'pregunta_id' => $pregunta->id,
                        'opcion' => mb_strtoupper(trim($opcion['opcion']), 'UTF-8'),
                        'valor' => chr(97 + $index),
                        'requiere_especificar' => $opcion['requiere_especificar'] ?? false,
                        'orden' => $index + 1,
                    ]);
                }
            }

            DB::commit();

            Log::info('Pregunta creada exitosamente', [
                'id' => $pregunta->id,
                'tipo' => $pregunta->tipo,
                'usuario' =>  Auth::id(),
                'ip' => $request->ip()
            ]);

            return redirect()->route('preguntas.index')
                ->with('success', 'Pregunta creada exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear pregunta', [
                'error' => $e->getMessage(),
                'usuario' =>  Auth::id()
            ]);
            
            return back()->withErrors(['error' => 'Error al crear la pregunta: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $pregunta = Pregunta::with('opciones')->findOrFail($id);
            return Inertia::render('preguntas/edit', [
                'pregunta' => $pregunta
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cargar pregunta para editar', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('preguntas.index')
                ->withErrors(['error' => 'La pregunta no existe']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate(
            $this->getReglasValidacion(),
            $this->getMensajesPersonalizados()
        );

        DB::beginTransaction();
        
        try {
            $pregunta = Pregunta::findOrFail($id);
            $descripcionFormateada = $this->formatearPregunta($validated['descripcion']);
            
            $existente = Pregunta::where('descripcion', $descripcionFormateada)
                ->where('id', '!=', $id)
                ->first();
                
            if ($existente) {
                DB::rollBack();
                return back()->withErrors([
                    'descripcion' => 'Ya existe otra pregunta con este texto.'
                ])->withInput();
            }
            
            $pregunta->update([
                'descripcion' => $descripcionFormateada,
                'activa' => $validated['activa'],
                'tipo' => $validated['tipo'],
            ]);

            // Eliminar opciones anteriores
            $pregunta->opciones()->delete();

            // Si es pregunta compuesta, guardar nuevas opciones
            if ($validated['tipo'] === 'multiple' && !empty($validated['opciones'])) {
                foreach ($validated['opciones'] as $index => $opcion) {
                    OpcionPregunta::create([
                        'pregunta_id' => $pregunta->id,
                        'opcion' => mb_strtoupper(trim($opcion['opcion']), 'UTF-8'),
                        'valor' => chr(97 + $index),
                        'requiere_especificar' => $opcion['requiere_especificar'] ?? false,
                        'orden' => $index + 1,
                    ]);
                }
            }

            DB::commit();

            Log::info('Pregunta actualizada exitosamente', [
                'id' => $pregunta->id,
                'tipo' => $pregunta->tipo,
                'usuario' =>  Auth::id(),
                'ip' => $request->ip()
            ]);

            return redirect()->route('preguntas.index')
                ->with('success', 'Pregunta actualizada exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar pregunta', [
                'id' => $id,
                'error' => $e->getMessage(),
                'usuario' =>  Auth::id()
            ]);
            
            return back()->withErrors(['error' => 'Error al actualizar la pregunta: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Toggle the active status of the specified resource.
     */
    public function toggle($id)
    {
        DB::beginTransaction();
        
        try {
            $pregunta = Pregunta::findOrFail($id);
            $pregunta->activa = !$pregunta->activa;
            $pregunta->save();

            DB::commit();

            Log::info('Estado de pregunta cambiado', [
                'id' => $pregunta->id,
                'nuevo_estado' => $pregunta->activa ? 'activa' : 'inactiva',
                'usuario' =>  Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'activa' => $pregunta->activa,
                'message' => $pregunta->activa ? 'Pregunta activada' : 'Pregunta desactivada'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al cambiar estado de pregunta', [
                'id' => $id,
                'error' => $e->getMessage(),
                'usuario' =>  Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $pregunta = Pregunta::findOrFail($id);

            // Verificar si tiene respuestas asociadas
            $respuestasCount = $pregunta->respuestas()->count();
            if ($respuestasCount > 0) {
                DB::rollBack();
                Log::warning('Intento de eliminar pregunta con respuestas asociadas', [
                    'id' => $id,
                    'respuestas_count' => $respuestasCount,
                    'usuario' =>  Auth::id()
                ]);
                
                return back()->withErrors([
                    'error' => "No se puede eliminar la pregunta porque tiene {$respuestasCount} respuestas asociadas."
                ]);
            }

            // Eliminar opciones
            $pregunta->opciones()->delete();
            
            $descripcion = $pregunta->descripcion;
            $pregunta->delete();

            DB::commit();

            Log::info('Pregunta eliminada exitosamente', [
                'id' => $id,
                'descripcion' => $descripcion,
                'usuario' =>  Auth::id()
            ]);

            return redirect()->route('preguntas.index')
                ->with('success', 'Pregunta eliminada exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar pregunta', [
                'id' => $id,
                'error' => $e->getMessage(),
                'usuario' =>  Auth::id()
            ]);
            
            return back()->withErrors(['error' => 'Error al eliminar la pregunta: ' . $e->getMessage()]);
        }
    }
}