// components/PreguntaInput.tsx
import { useState } from 'react';

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
    tipo: 'simple' | 'multiple';
    opciones?: Opcion[];
}

interface PreguntaInputProps {
    pregunta: Pregunta;
    data: any;
    setData: (key: string, value: any) => void;
}

export default function PreguntaInput({ pregunta, data, setData }: PreguntaInputProps) {
    const [especificacion, setEspecificacion] = useState('');
    const [opcionSeleccionada, setOpcionSeleccionada] = useState<number | null>(null);

    // Para preguntas simples (Sí/No)
    const handleRespuestaSimple = (valor: string) => {
        setData('respuestas', {
            ...data.respuestas,
            [pregunta.id]: valor
        });
    };

    // Para preguntas múltiples
    const handleRespuestaMultiple = (opcionId: number, requiereEspecificar: boolean) => {
        setOpcionSeleccionada(opcionId);
        
        // Guardar la selección
        setData('respuestas_multiple', {
            ...data.respuestas_multiple,
            [pregunta.id]: {
                opcion_id: opcionId,
                especificacion: requiereEspecificar ? especificacion : ''
            }
        });
    };

    const handleEspecificacion = (opcionId: number, texto: string) => {
        setEspecificacion(texto);
        
        // Actualizar la especificación
        setData('respuestas_multiple', {
            ...data.respuestas_multiple,
            [pregunta.id]: {
                opcion_id: opcionId,
                especificacion: texto
            }
        });
    };

    if (pregunta.tipo === 'simple') {
        return (
            <div className="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm">
                <label className="mb-3 block text-sm font-semibold text-neutral-800">
                    {pregunta.descripcion}
                </label>
                <div className="flex gap-4">
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            name={`pregunta_${pregunta.id}`}
                            value="si"
                            checked={data.respuestas[pregunta.id] === 'si'}
                            onChange={() => handleRespuestaSimple('si')}
                            className="w-4 h-4 text-[#1FB7E9]"
                        />
                        <span className="text-sm text-neutral-700">Sí</span>
                    </label>
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            name={`pregunta_${pregunta.id}`}
                            value="no"
                            checked={data.respuestas[pregunta.id] === 'no'}
                            onChange={() => handleRespuestaSimple('no')}
                            className="w-4 h-4 text-[#1FB7E9]"
                        />
                        <span className="text-sm text-neutral-700">No</span>
                    </label>
                </div>
            </div>
        );
    }

    // Para preguntas múltiples
    return (
        <div className="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm">
            <label className="mb-3 block text-sm font-semibold text-neutral-800">
                {pregunta.descripcion}
            </label>
            <div className="space-y-2">
                {pregunta.opciones?.map((opcion) => (
                    <div key={opcion.id} className="space-y-1">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="radio"
                                name={`pregunta_${pregunta.id}`}
                                value={opcion.id}
                                checked={opcionSeleccionada === opcion.id}
                                onChange={() => handleRespuestaMultiple(opcion.id, opcion.requiere_especificar)}
                                className="w-4 h-4 text-[#1FB7E9]"
                            />
                            <span className="text-sm text-neutral-700">{opcion.opcion}</span>
                        </label>
                        
                        {opcionSeleccionada === opcion.id && opcion.requiere_especificar && (
                            <div className="ml-6 mt-2">
                                <input
                                    type="text"
                                    placeholder="Especifique..."
                                    value={especificacion}
                                    onChange={(e) => handleEspecificacion(opcion.id, e.target.value)}
                                    className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-[#1FB7E9] focus:outline-none focus:ring-1 focus:ring-[#1FB7E9]"
                                />
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}