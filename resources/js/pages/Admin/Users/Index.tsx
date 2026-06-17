import React, { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import InputField from '@/components/input-field';
import PasswordInput from '@/components/PasswordInput'; // ← Importar el componente
import { useForm, router, Head } from '@inertiajs/react';
import { Pencil, Trash2, X } from 'lucide-react';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';

const MySwal = withReactContent(Swal);

interface BreadcrumbItem {
    title: string;
    href: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    roles: { name: string }[];
}

interface Role {
    id: number;
    name: string;
}

interface Props {
    users: User[];
    roles: Role[];
    flash?: {
        success?: string;
        error?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Usuarios', href: '#' },
];

export default function UserIndex({ users, roles, flash }: Props) {
    const [editUser, setEditUser] = useState<User | null>(null);
    const [isEditing, setIsEditing] = useState(false);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: '',
    });

    const resetForm = () => {
        reset();
        setEditUser(null);
        setIsEditing(false);
    };

    const handleEdit = (user: User) => {
        setEditUser(user);
        setIsEditing(true);
        setData({
            name: user.name,
            email: user.email,
            password: '',
            password_confirmation: '',
            role: user.roles[0]?.name || '',
        });
        document.getElementById('formulario-usuario')?.scrollIntoView({ behavior: 'smooth' });
    };

    const handleCancelEdit = () => {
        resetForm();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEditing && editUser) {
            put(route('admin.users.update', editUser.id), {
                onSuccess: () => {
                    resetForm();
                    MySwal.fire({
                        title: '¡Actualizado!',
                        text: 'El usuario ha sido actualizado correctamente.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: (errors) => {
                    MySwal.fire({
                        title: 'Error',
                        text: Object.values(errors).join(', '),
                        icon: 'error',
                        confirmButtonColor: '#EF4444'
                    });
                }
            });
        } else {
            post(route('admin.users.store'), {
                onSuccess: () => {
                    resetForm();
                    MySwal.fire({
                        title: '¡Creado!',
                        text: 'El usuario ha sido creado correctamente.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: (errors) => {
                    MySwal.fire({
                        title: 'Error',
                        text: Object.values(errors).join(', '),
                        icon: 'error',
                        confirmButtonColor: '#EF4444'
                    });
                }
            });
        }
    };

    const handleDelete = (id: number, name: string) => {
        MySwal.fire({
            title: '¿Está seguro?',
            html: `El usuario <strong>"${name}"</strong> será eliminado permanentemente.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('admin.users.destroy', id), {
                    onSuccess: () => {
                        MySwal.fire({
                            title: '¡Eliminado!',
                            text: 'El usuario ha sido eliminado.',
                            icon: 'success',
                            confirmButtonColor: '#1FB7E9'
                        });
                    },
                    onError: (errors) => {
                        MySwal.fire({
                            title: 'Error',
                            text: (errors as any).error || 'No se pudo eliminar el usuario',
                            icon: 'error',
                            confirmButtonColor: '#EF4444'
                        });
                    }
                });
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Usuarios" />

            <div className="w-full space-y-6 p-10">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-neutral-800 tracking-tight">Gestión de Usuarios</h1>
                        <p className="text-sm text-neutral-500 mt-1">Administra los encuestadores y sus permisos</p>
                    </div>
                </div>

                {flash?.success && (
                    <div className="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        {flash.error}
                    </div>
                )}

                {/* Formulario */}
                <div id="formulario-usuario" className="bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-lg font-semibold text-neutral-800">
                            {isEditing ? 'Editar Usuario' : 'Registrar Nuevo Encuestador'}
                        </h2>
                        {isEditing && (
                            <button
                                onClick={handleCancelEdit}
                                className="text-sm text-neutral-500 hover:text-neutral-700 flex items-center gap-1"
                            >
                                <X className="w-4 h-4" />
                                Cancelar edición
                            </button>
                        )}
                    </div>

                    <form onSubmit={submit} className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <InputField
                            label="Nombre"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            placeholder="Nombre completo"
                            required
                        />
                        <InputField
                            label="Email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                            placeholder="correo@ejemplo.com"
                            type="email"
                            required
                        />
                        
                        {/* ✅ Campo Contraseña con PasswordInput */}
                        <PasswordInput
                            label={isEditing ? 'Nueva Contraseña (opcional)' : 'Contraseña'}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                            placeholder={isEditing ? 'Dejar en blanco para mantener' : 'Mínimo 8 caracteres'}
                            required={!isEditing}
                            id="password"
                            autoComplete={isEditing ? 'new-password' : 'current-password'}
                        />
                        
                        {/* ✅ Campo Confirmar Contraseña con PasswordInput */}
                        {isEditing ? (
                            data.password && (
                                <PasswordInput
                                    label="Confirmar Nueva Contraseña"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    error={errors.password_confirmation}
                                    placeholder="Repite la contraseña"
                                    required
                                    id="password_confirmation"
                                    autoComplete="new-password"
                                />
                            )
                        ) : (
                            <PasswordInput
                                label="Confirmar Contraseña"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                error={errors.password_confirmation}
                                placeholder="Repite la contraseña"
                                required
                                id="password_confirmation"
                                autoComplete="new-password"
                            />
                        )}
                        
                        <div>
                            <label className="block text-sm font-medium text-neutral-700 mb-1">Rol</label>
                            <select
                                className="w-full rounded-lg border border-neutral-300 px-4 py-2.5 text-sm focus:border-[#1FB7E9] focus:outline-none focus:ring-2 focus:ring-[#1FB7E9]/20 transition-all"
                                value={data.role}
                                onChange={(e) => setData('role', e.target.value)}
                                required
                            >
                                <option value="">Seleccionar...</option>
                                {roles.map((role: any) => (
                                    <option key={role.id} value={role.name}>
                                        {role.name.toUpperCase()}
                                    </option>
                                ))}
                            </select>
                            {errors.role && <p className="mt-1 text-xs text-red-600">{errors.role}</p>}
                        </div>

                        <div className="md:col-span-4 flex justify-start gap-3 mt-4 pt-4 border-t border-neutral-200">
                            <button
                                type="submit"
                                disabled={processing}
                                className="bg-[#1FB7E9] text-white px-6 py-2.5 text-sm font-bold rounded-lg hover:bg-[#1699c2] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? (isEditing ? 'Actualizando...' : 'Registrando...') : (isEditing ? 'Actualizar Usuario' : 'Registrar Usuario')}
                            </button>
                            <button
                                type="button"
                                onClick={() => window.history.back()}
                                className="border-2 border-neutral-300 bg-white text-neutral-700 px-6 py-2.5 text-sm font-semibold rounded-lg hover:bg-neutral-50 transition-colors"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>

                {/* Tabla de usuarios */}
                <div className="bg-white rounded-xl shadow-sm overflow-hidden border border-neutral-200">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead className="bg-[#1FB7E9] border-b">
                                <tr>
                                    <th className="px-6 py-4 text-sm font-bold text-white">Usuario</th>
                                    <th className="px-6 py-4 text-sm font-bold text-white">Email</th>
                                    <th className="px-6 py-4 text-sm font-bold text-white">Rol</th>
                                    <th className="px-6 py-4 text-sm font-bold text-white text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-100">
                                {users.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-12 text-center text-sm text-neutral-400 italic">
                                            No hay usuarios registrados
                                        </td>
                                    </tr>
                                ) : (
                                    users.map((user: any) => (
                                        <tr key={user.id} className="hover:bg-neutral-50/50 transition-colors">
                                            <td className="px-6 py-4 text-sm font-medium text-neutral-900">
                                                {user.name}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-neutral-600">
                                                {user.email}
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <span className="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-semibold">
                                                    {user.roles[0]?.name || 'Sin Rol'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <div className="flex items-center justify-center gap-2">
                                                    <button
                                                        onClick={() => handleEdit(user)}
                                                        className="text-amber-500 hover:text-amber-700 p-2 rounded-lg hover:bg-amber-50 transition-colors"
                                                        title="Editar usuario"
                                                    >
                                                        <Pencil size={18} />
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(user.id, user.name)}
                                                        className="text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 transition-colors"
                                                        title="Eliminar usuario"
                                                    >
                                                        <Trash2 size={18} />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="px-6 py-3 bg-neutral-50 border-t border-neutral-100 flex justify-between items-center text-xs text-neutral-500">
                        <span>Total: <span className="font-semibold text-neutral-700">{users.length}</span> usuarios</span>
                        <span>Roles disponibles: {roles.map((r: any) => r.name.toUpperCase()).join(', ')}</span>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}