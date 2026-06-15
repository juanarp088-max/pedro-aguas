import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import { FormEvent, useState, useEffect } from 'react';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';

const MySwal = withReactContent(Swal);

interface BreadcrumbItem {
    title: string;
    href: string;
}

interface Opcion {
    id: number;
    opcion: string;
    valor: string;
    requiere_especificar: boolean;
    orden: number;
}

interface Pregunta {
    id: number;
    descripcion: string;
    activa: boolean;
    tipo: 'simple' | 'multiple';
    opciones?: Opcion[];
    created_at: string;
    updated_at: string;
}

declare const route: any;

export default function PreguntasEdit() {
    const { props } = usePage<any>();
    const pregunta: Pregunta = props.pregunta;
    const [processing, setProcessing] = useState(false);
    const [tipo, setTipo] = useState<'simple' | 'multiple'>(pregunta.tipo || 'simple');
    const [descripcion, setDescripcion] = useState(pregunta.descripcion || '');
    const [activa, setActiva] = useState(pregunta.activa ?? true);
    const [opciones, setOpciones] = useState<{ opcion: string; requiere_especificar: boolean; id?: number }[]>([]);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Preguntas', href: route('preguntas.index') },
        { title: `Editar Pregunta #${pregunta.id}`, href: '#' },
    ];

    // Inicializar opciones cuando se carga la pregunta
    useEffect(() => {
        console.log('Pregunta completa:', pregunta);
        
        if (pregunta.tipo === 'multiple' && pregunta.opciones && pregunta.opciones.length > 0) {
            setOpciones(pregunta.opciones.map(op => ({
                id: op.id,
                opcion: op.opcion,
                requiere_especificar: op.requiere_especificar
            })));
        } else if (pregunta.tipo === 'multiple') {
            // Si es múltiple pero no tiene opciones, agregar una vacía
            setOpciones([{ opcion: '', requiere_especificar: false }]);
        }
        
        setTipo(pregunta.tipo || 'simple');
        setDescripcion(pregunta.descripcion || '');
        setActiva(pregunta.activa ?? true);
    }, [pregunta]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        
        const submitData: any = {
            descripcion: descripcion,
            activa: activa,
            tipo: tipo,
        };
        
        if (tipo === 'multiple') {
            submitData.opciones = opciones.filter(o => o.opcion.trim() !== '');
        }
        
        console.log('Enviando datos:', submitData);
        
        router.put(route('preguntas.update', pregunta.id), submitData, {
            preserveScroll: true,
            onSuccess: () => {
                setProcessing(false);
                MySwal.fire({
                    title: '¡Pregunta Actualizada!',
                    text: 'Los cambios han sido guardados exitosamente.',
                    icon: 'success',
                    confirmButtonColor: '#1FB7E9',
                    timer: 2000
                });
            },
            onError: (errorResponse) => {
                setProcessing(false);
                console.error('Error:', errorResponse);
                setErrors(errorResponse);
                const errorMessage = Object.values(errorResponse).join(', ');
                MySwal.fire({
                    title: 'Error',
                    text: errorMessage || 'Ocurrió un error al actualizar la pregunta',
                    icon: 'error',
                    confirmButtonColor: '#EF4444'
                });
            }
        });
    };

    const agregarOpcion = () => {
        setOpciones([...opciones, { opcion: '', requiere_especificar: false }]);
    };

    const eliminarOpcion = (index: number) => {
        if (opciones.length > 1) {
            setOpciones(opciones.filter((_, i) => i !== index));
        }
    };

    const actualizarOpcion = (index: number, campo: 'opcion' | 'requiere_especificar', valor: string | boolean) => {
        const nuevasOpciones = [...opciones];
        nuevasOpciones[index] = { ...nuevasOpciones[index], [campo]: valor };
        setOpciones(nuevasOpciones);
    };

    // Si la pregunta no existe o hay error
    if (!pregunta || !pregunta.id) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="w-full space-y-6 p-10">
                    <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-center">
                        <h2 className="text-xl font-bold text-red-800">Error</h2>
                        <p className="text-red-600">No se pudo cargar la pregunta solicitada.</p>
                        <a href={route('preguntas.index')} className="mt-4 inline-block rounded-lg bg-[#1FB7E9] px-6 py-2 text-white">
                            Volver al listado
                        </a>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Editar Pregunta" />

            <div className="w-full space-y-6 p-10">
                <div className="rounded-xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
                    <div className="px-8 pt-6 pb-4 border-b border-neutral-100 bg-gradient-to-r from-white to-neutral-50">
                        <h2 className="text-2xl font-bold text-neutral-800 tracking-tight">Editar Pregunta</h2>
                        <p className="text-sm text-neutral-500 mt-1">Modifique los datos de la pregunta de evaluación</p>
                    </div>

                    <div className="p-8">
                        <form onSubmit={handleSubmit} className="space-y-8">
                            {/* Tipo de Pregunta */}
                            <div>
                                <h3 className="text-base font-bold text-neutral-800 mb-4 pb-2 border-b border-neutral-200">Tipo de Pregunta</h3>
                                <div className="flex gap-6">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            value="simple"
                                            checked={tipo === 'simple'}
                                            onChange={() => setTipo('simple')}
                                            className="w-4 h-4 text-[#1FB7E9] focus:ring-[#1FB7E9]"
                                        />
                                        <span className="text-sm text-neutral-700">Simple (Sí/No)</span>
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            value="multiple"
                                            checked={tipo === 'multiple'}
                                            onChange={() => setTipo('multiple')}
                                            className="w-4 h-4 text-[#1FB7E9] focus:ring-[#1FB7E9]"
                                        />
                                        <span className="text-sm text-neutral-700">Compuesta (Opciones múltiples)</span>
                                    </label>
                                </div>
                            </div>

                            {/* Datos de la Pregunta */}
                            <div>
                                <h3 className="text-base font-bold text-neutral-800 mb-4 pb-2 border-b border-neutral-200">Datos de la Pregunta</h3>
                                <div className="grid grid-cols-1 gap-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-neutral-700 mb-2">
                                            Pregunta <span className="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            value={descripcion}
                                            onChange={(e) => setDescripcion(e.target.value)}
                                            rows={4}
                                            className="w-full rounded-lg border border-neutral-300 px-4 py-2.5 text-sm focus:border-[#1FB7E9] focus:outline-none focus:ring-2 focus:ring-[#1FB7E9]/20 transition-all resize-none"
                                        />
                                        <p className="mt-1 text-xs text-neutral-500">
                                            La pregunta se mostrará en mayúsculas en el formulario de registro
                                        </p>
                                        {errors.descripcion && (
                                            <p className="mt-1 text-xs text-red-600">{errors.descripcion}</p>
                                        )}
                                    </div>

                                    <div className="bg-neutral-50 p-4 rounded-lg">
                                        <label className="flex items-center gap-3 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={activa}
                                                onChange={(e) => setActiva(e.target.checked)}
                                                className="w-4 h-4 rounded border-neutral-300 text-[#1FB7E9] focus:ring-[#1FB7E9]"
                                            />
                                            <span className="text-sm font-medium text-neutral-700">Pregunta activa</span>
                                        </label>
                                        <p className="mt-2 text-xs text-neutral-500 ml-7">
                                            Las preguntas inactivas no se mostrarán en el formulario de registro de beneficiarios
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Opciones para preguntas compuestas */}
                            {tipo === 'multiple' && (
                                <div>
                                    <h3 className="text-base font-bold text-neutral-800 mb-4 pb-2 border-b border-neutral-200">Opciones de Respuesta</h3>
                                    <div className="space-y-3">
                                        {opciones.map((opcion, index) => (
                                            <div key={opcion.id || index} className="flex gap-3 items-start">
                                                <div className="flex-1">
                                                    <input
                                                        type="text"
                                                        value={opcion.opcion}
                                                        onChange={(e) => actualizarOpcion(index, 'opcion', e.target.value)}
                                                        placeholder={`Opción ${String.fromCharCode(97 + index)}`}
                                                        className="w-full rounded-lg border border-neutral-300 px-4 py-2 text-sm focus:border-[#1FB7E9] focus:outline-none focus:ring-2 focus:ring-[#1FB7E9]/20"
                                                    />
                                                </div>
                                                <label className="flex items-center gap-2 whitespace-nowrap">
                                                    <input
                                                        type="checkbox"
                                                        checked={opcion.requiere_especificar}
                                                        onChange={(e) => actualizarOpcion(index, 'requiere_especificar', e.target.checked)}
                                                        className="w-4 h-4 rounded border-neutral-300 text-[#1FB7E9] focus:ring-[#1FB7E9]"
                                                    />
                                                    <span className="text-xs text-neutral-600">Especificar</span>
                                                </label>
                                                {opciones.length > 1 && (
                                                    <button
                                                        type="button"
                                                        onClick={() => eliminarOpcion(index)}
                                                        className="text-red-500 hover:text-red-700 p-1"
                                                    >
                                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                )}
                                            </div>
                                        ))}
                                        <button
                                            type="button"
                                            onClick={agregarOpcion}
                                            className="text-[#1FB7E9] hover:text-[#1699c2] text-sm font-medium flex items-center gap-1 mt-2"
                                        >
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                            </svg>
                                            Agregar opción
                                        </button>
                                    </div>
                                    {errors.opciones && (
                                        <p className="mt-2 text-xs text-red-600">{errors.opciones}</p>
                                    )}
                                </div>
                            )}

                            <div className="flex justify-end gap-4 pt-6 border-t border-neutral-200">
                                <a
                                    href={route('preguntas.index')}
                                    className="rounded-lg border-2 border-neutral-300 bg-white px-6 py-2.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 hover:border-neutral-400 transition-all duration-200"
                                >
                                    Cancelar
                                </a>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-lg bg-[#1FB7E9] px-8 py-2.5 text-sm font-bold text-white shadow-md hover:bg-[#1699c2] hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-[1.02] inline-flex items-center gap-2"
                                >
                                    {processing ? (
                                        <>
                                            <svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Guardando...
                                        </>
                                    ) : (
                                        'Actualizar Pregunta'
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {/* Información adicional */}
                <div className="text-center text-xs text-neutral-400">
                    ID: {pregunta.id} | Tipo: {pregunta.tipo === 'simple' ? 'Sí/No' : 'Opciones Múltiples'} | 
                    Creada: {new Date(pregunta.created_at).toLocaleDateString()} | 
                    Última actualización: {new Date(pregunta.updated_at).toLocaleDateString()}
                </div>
            </div>
        </AppLayout>
    );
}