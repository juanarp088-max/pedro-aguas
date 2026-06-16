import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';

const MySwal = withReactContent(Swal);

interface BreadcrumbItem {
    title: string;
    href: string;
}

declare const route: any;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exportar Beneficiarios', href: '#' },
];

const camposDisponibles = [
    { id: 'id', nombre: 'ID' },
    { id: 'nombre', nombre: 'Primer Nombre' },
    { id: 'snombre', nombre: 'Segundo Nombre' },
    { id: 'apellido', nombre: 'Primer Apellido' },
    { id: 'sapellido', nombre: 'Segundo Apellido' },
    { id: 'nacimiento', nombre: 'Fecha Nacimiento' },
    { id: 'edad', nombre: 'Edad' },
    { id: 'genero', nombre: 'Género' },
    { id: 'telefono', nombre: 'Teléfono' },
    { id: 'colonia', nombre: 'Colonia' },
    { id: 'calle', nombre: 'Calle' },
    { id: 'numext', nombre: 'Número Exterior' },
    { id: 'numint', nombre: 'Número Interior' },
    { id: 'municipio', nombre: 'Municipio' },
    { id: 'cp', nombre: 'Código Postal' },
    { id: 'tarjeta_soluciones', nombre: 'Tarjeta Soluciones' },
    { id: 'duplicado', nombre: 'Registro Duplicado' },
    { id: 'fecha_registro', nombre: 'Fecha Registro' },
    { id: 'hora_registro', nombre: 'Hora Registro' },
];

export default function Exportar() {
    const [fechaInicio, setFechaInicio] = useState('');
    const [fechaFin, setFechaFin] = useState('');
    const [incluirRespuestas, setIncluirRespuestas] = useState(false);
    const [exportando, setExportando] = useState(false);
    const [tipoExportacion, setTipoExportacion] = useState<'rango' | 'todos'>('rango');
    const [camposSeleccionados, setCamposSeleccionados] = useState<string[]>([]);
    const [seleccionarTodos, setSeleccionarTodos] = useState(false);

    const handleSeleccionarTodos = () => {
        if (seleccionarTodos) {
            setCamposSeleccionados([]);
            setSeleccionarTodos(false);
        } else {
            setCamposSeleccionados(camposDisponibles.map(c => c.id));
            setSeleccionarTodos(true);
        }
    };

    const handleToggleCampo = (campoId: string) => {
        if (camposSeleccionados.includes(campoId)) {
            const nuevos = camposSeleccionados.filter(c => c !== campoId);
            setCamposSeleccionados(nuevos);
            // Si no hay campos seleccionados, desmarcar "Seleccionar todos"
            if (nuevos.length === 0) {
                setSeleccionarTodos(false);
            }
        } else {
            const nuevos = [...camposSeleccionados, campoId];
            setCamposSeleccionados(nuevos);
            // Si están todos seleccionados, marcar "Seleccionar todos"
            if (nuevos.length === camposDisponibles.length) {
                setSeleccionarTodos(true);
            }
        }
    };

    const handleExportar = async () => {
        // Validaciones
        if (tipoExportacion === 'rango' && (!fechaInicio || !fechaFin)) {
            MySwal.fire({
                title: 'Campos incompletos',
                text: 'Por favor seleccione ambas fechas',
                icon: 'warning',
                confirmButtonColor: '#EF4444'
            });
            return;
        }

        // Si no hay campos seleccionados y no está marcado "Seleccionar todos"
        if (camposSeleccionados.length === 0 && !seleccionarTodos) {
            MySwal.fire({
                title: 'Campos no seleccionados',
                text: 'Por favor seleccione al menos un campo para exportar',
                icon: 'warning',
                confirmButtonColor: '#EF4444'
            });
            return;
        }

        setExportando(true);

        try {
            // Construir URL con parámetros
            const params = new URLSearchParams();
            
            // Añadir parámetros según el tipo de exportación
            if (tipoExportacion === 'rango') {
                params.append('fecha_inicio', fechaInicio);
                params.append('fecha_fin', fechaFin);
            }
            
            params.append('incluir_respuestas', incluirRespuestas ? '1' : '0');
            
            // Determinar qué campos enviar
            let camposParaEnviar = [];
            if (seleccionarTodos) {
                // Si está marcado "Seleccionar todos", enviar TODOS los campos
                camposParaEnviar = camposDisponibles.map(c => c.id);
            } else {
                // Si no, enviar solo los seleccionados
                camposParaEnviar = camposSeleccionados;
            }
            
            // Añadir cada campo como parámetro individual
            camposParaEnviar.forEach(campo => {
                params.append('campos[]', campo);
            });

            // Determinar la ruta según el tipo de exportación
            const ruta = tipoExportacion === 'rango' ? 'exportacion.rango' : 'exportacion.todos';
            const url = route(ruta) + '?' + params.toString();

            console.log('=== EXPORTACIÓN ===');
            console.log('URL:', url);
            console.log('Campos a exportar:', camposParaEnviar);
            console.log('Incluir respuestas:', incluirRespuestas);
            console.log('Tipo exportación:', tipoExportacion);
            if (tipoExportacion === 'rango') {
                console.log('Fecha inicio:', fechaInicio);
                console.log('Fecha fin:', fechaFin);
            }

            // Abrir en nueva ventana para descarga
            const ventana = window.open(url, '_blank');
            
            // Si la ventana se bloqueó, mostrar mensaje
            if (!ventana) {
                MySwal.fire({
                    title: 'Ventana bloqueada',
                    text: 'Por favor permita ventanas emergentes para descargar el archivo',
                    icon: 'warning',
                    confirmButtonColor: '#1FB7E9'
                });
                setExportando(false);
                return;
            }

            // Mostrar mensaje de éxito
            MySwal.fire({
                title: 'Exportación iniciada',
                text: 'El archivo Excel se está generando y descargará automáticamente',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
            
        } catch (error) {
            console.error('Error al exportar:', error);
            MySwal.fire({
                title: 'Error',
                text: 'No se pudo generar el archivo Excel. Por favor intente nuevamente.',
                icon: 'error',
                confirmButtonColor: '#EF4444'
            });
        } finally {
            setExportando(false);
        }
    };

    const today = new Date().toISOString().split('T')[0];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Exportar Beneficiarios" />

            <div className="w-full space-y-6 p-10">
                <div className="rounded-xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
                    <div className="px-8 pt-6 pb-4 border-b border-neutral-100 bg-gradient-to-r from-white to-neutral-50">
                        <h2 className="text-2xl font-bold text-neutral-800 tracking-tight">Exportar Beneficiarios</h2>
                        <p className="text-sm text-neutral-500 mt-1">Seleccione el tipo de exportación y los campos a incluir</p>
                    </div>

                    <div className="p-8">
                        <div className="space-y-6">
                            {/* Tipo de exportación */}
                            <div>
                                <label className="block text-sm font-semibold text-neutral-700 mb-3">
                                    Tipo de Exportación
                                </label>
                                <div className="flex gap-6">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            value="rango"
                                            checked={tipoExportacion === 'rango'}
                                            onChange={() => setTipoExportacion('rango')}
                                            className="w-4 h-4 text-[#1FB7E9] focus:ring-[#1FB7E9]"
                                        />
                                        <span className="text-sm text-neutral-700">Por rango de fechas</span>
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            value="todos"
                                            checked={tipoExportacion === 'todos'}
                                            onChange={() => setTipoExportacion('todos')}
                                            className="w-4 h-4 text-[#1FB7E9] focus:ring-[#1FB7E9]"
                                        />
                                        <span className="text-sm text-neutral-700">Todos los registros</span>
                                    </label>
                                </div>
                            </div>

                            {/* Rango de fechas */}
                            {tipoExportacion === 'rango' && (
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label className="block text-sm font-semibold text-neutral-700 mb-2">
                                            Fecha de Inicio <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            value={fechaInicio}
                                            onChange={(e) => setFechaInicio(e.target.value)}
                                            max={today}
                                            className="w-full rounded-lg border border-neutral-300 px-4 py-2.5 text-sm focus:border-[#1FB7E9] focus:outline-none focus:ring-2 focus:ring-[#1FB7E9]/20"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-neutral-700 mb-2">
                                            Fecha de Fin <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            value={fechaFin}
                                            onChange={(e) => setFechaFin(e.target.value)}
                                            min={fechaInicio}
                                            max={today}
                                            className="w-full rounded-lg border border-neutral-300 px-4 py-2.5 text-sm focus:border-[#1FB7E9] focus:outline-none focus:ring-2 focus:ring-[#1FB7E9]/20"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Selección de campos */}
                            <div>
                                <label className="block text-sm font-semibold text-neutral-700 mb-3">
                                    Campos a exportar
                                </label>
                                <div className="mb-3">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={seleccionarTodos}
                                            onChange={handleSeleccionarTodos}
                                            className="w-4 h-4 rounded border-neutral-300 text-[#1FB7E9] focus:ring-[#1FB7E9]"
                                        />
                                        <span className="text-sm font-medium text-neutral-700">
                                            {seleccionarTodos ? 'Deseleccionar todos' : 'Seleccionar todos los campos'}
                                        </span>
                                    </label>
                                </div>
                                <div className="grid grid-cols-2 gap-3 md:grid-cols-4 border border-neutral-200 rounded-lg p-4 max-h-60 overflow-y-auto">
                                    {camposDisponibles.map((campo) => (
                                        <label key={campo.id} className="flex items-center gap-2 cursor-pointer hover:bg-neutral-50 p-1 rounded transition-colors">
                                            <input
                                                type="checkbox"
                                                checked={camposSeleccionados.includes(campo.id)}
                                                onChange={() => handleToggleCampo(campo.id)}
                                                className="w-4 h-4 rounded border-neutral-300 text-[#1FB7E9] focus:ring-[#1FB7E9]"
                                            />
                                            <span className="text-sm text-neutral-700">{campo.nombre}</span>
                                        </label>
                                    ))}
                                </div>
                                <p className="mt-2 text-xs text-neutral-500">
                                    {camposSeleccionados.length} de {camposDisponibles.length} campos seleccionados
                                </p>
                            </div>

                            {/* Opciones adicionales */}
                            <div className="bg-neutral-50 p-4 rounded-lg border border-neutral-200">
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={incluirRespuestas}
                                        onChange={(e) => setIncluirRespuestas(e.target.checked)}
                                        className="w-4 h-4 rounded border-neutral-300 text-[#1FB7E9] focus:ring-[#1FB7E9]"
                                    />
                                    <span className="text-sm font-medium text-neutral-700">
                                        Incluir respuestas de evaluación social
                                    </span>
                                </label>
                                <p className="mt-2 text-xs text-neutral-500 ml-7">
                                    Esto agregará columnas adicionales con las respuestas de cada pregunta
                                </p>
                                {incluirRespuestas && (
                                    <div className="mt-2 ml-7 p-2 bg-blue-50 rounded border border-blue-200">
                                        <p className="text-xs text-blue-700">
                                            ✅ Se incluirán todas las preguntas activas como columnas adicionales
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Información del rango */}
                            {tipoExportacion === 'rango' && fechaInicio && fechaFin && (
                                <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <p className="text-sm text-blue-800">
                                        📊 Se exportarán los registros creados entre el <strong>{fechaInicio}</strong> y el <strong>{fechaFin}</strong>
                                    </p>
                                    <p className="text-xs text-blue-600 mt-1">
                                        {camposSeleccionados.length} campos seleccionados {incluirRespuestas ? ' + respuestas de evaluación' : ''}
                                    </p>
                                </div>
                            )}

                            {tipoExportacion === 'todos' && (
                                <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <p className="text-sm text-blue-800">
                                        📊 Se exportarán TODOS los registros de beneficiarios
                                    </p>
                                    <p className="text-xs text-blue-600 mt-1">
                                        {camposSeleccionados.length} campos seleccionados {incluirRespuestas ? ' + respuestas de evaluación' : ''}
                                    </p>
                                </div>
                            )}

                            {/* Botones */}
                            <div className="flex justify-end gap-4 pt-6 border-t border-neutral-200">
                                <button
                                    type="button"
                                    onClick={() => window.history.back()}
                                    className="rounded-lg border-2 border-neutral-300 bg-white px-6 py-2.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 hover:border-neutral-400 transition-all duration-200"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    onClick={handleExportar}
                                    disabled={exportando || (tipoExportacion === 'rango' && (!fechaInicio || !fechaFin))}
                                    className="rounded-lg bg-[#1FB7E9] px-8 py-2.5 text-sm font-bold text-white shadow-md hover:bg-[#1699c2] hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-[1.02] inline-flex items-center gap-2"
                                >
                                    {exportando ? (
                                        <>
                                            <svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Generando...
                                        </>
                                    ) : (
                                        <>
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Exportar a Excel
                                        </>
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="text-center text-xs text-neutral-400">
                    Los archivos se generan en formato Excel (.xlsx) y se descargan automáticamente
                </div>
            </div>
        </AppLayout>
    );
}