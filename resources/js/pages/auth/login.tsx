import { Head, useForm } from '@inertiajs/react';
import React from 'react';
import PasswordInput from '@/components/PasswordInput'; // ← Importar el componente

export default function Login({ status }: any) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen bg-[#f3f4f6] flex flex-col items-center justify-center p-4">
            <Head title="Iniciar Sesión" />

            <div className="w-full max-w-md bg-white rounded-2xl border border-[#e5e7eb] overflow-hidden shadow-2xl shadow-blue-950/40">
                <div className="flex justify-center w-full">
                    <img 
                        src="/images/mesa1.webp" 
                        alt="Logo IAPAM" 
                        className="h-40 object-contain"
                    />
                </div>
                
                <div className="bg-[#1d3557] border-b border-[#e5e7eb] py-3 text-center">
                    <span className="text-white font-medium text-base">
                        Portal Institucional del IAPAM
                    </span>
                </div>

                <div className="p-8">
                    <h2 className="text-[#1e3a8a] text-2xl font-bold text-center mb-2">
                        Ingresa en tu cuenta
                    </h2>
                    <p className="text-gray-600 text-sm text-center mb-6">
                        Ingresa tu usuario y contraseña para iniciar sesión.
                    </p>

                    {status && (
                        <div className="mb-4 text-sm font-medium text-green-600 text-center">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-4">
                        {/* Campo: Correo */}
                        <div>
                            <label className="block text-gray-700 font-medium text-sm mb-1">
                                Dirección de correo
                            </label>
                            <input
                                type="email"
                                value={data.email}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900"
                                onChange={(e) => setData('email', e.target.value)}
                                required
                            />
                            {errors.email && <span className="text-red-500 text-xs mt-1 block">{errors.email}</span>}
                        </div>

                        {/* ✅ Campo: Contraseña con PasswordInput */}
                        <PasswordInput
                            label="Contraseña"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                            placeholder="Ingresa tu contraseña"
                            required
                            id="password"
                            autoComplete="current-password"
                            className="text-gray-900"
                        />

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full bg-[#1d3557] hover:bg-[#152844] text-white font-medium py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2 mt-6 relative"
                        >
                            <span className="text-base font-semibold">Ingresar</span>
                        </button>
                    </form>
                </div>

                <div className="bg-[#f9fafb] border-t border-[#e5e7eb] py-3 text-center">
                    <p className="text-xs text-gray-500">
                        © 2026 Portal de Captura de Datos Personas Adultas Mayores.
                    </p>
                </div>
            </div>
        </div>
    );
}