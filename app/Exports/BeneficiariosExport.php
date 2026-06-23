<?php

namespace App\Exports;

use App\Models\Registro;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithChunkReading; // 🔥 AGREGAR ESTA LÍNEA
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Illuminate\Support\Facades\Log;

class BeneficiariosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents , WithChunkReading
{
    protected $filtros;
    protected $incluirRespuestas;
    protected $fechaInicio;
    protected $fechaFin;
    protected $camposSeleccionados;
    protected $preguntasCache = null;

    public function __construct($filtros = [], $incluirRespuestas = false, $fechaInicio = null, $fechaFin = null, $camposSeleccionados = [])
    {
        $this->filtros = $filtros;
        $this->incluirRespuestas = $incluirRespuestas;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->camposSeleccionados = $camposSeleccionados;
    }

    public function collection()
    {
        Log::info('=== EXPORTACIÓN DE BENEFICIARIOS ===');
        Log::info('Fechas recibidas:', [
            'inicio' => $this->fechaInicio,
            'fin' => $this->fechaFin
        ]);

        $query = Registro::with(['respuestas.pregunta']);

        if ($this->fechaInicio && $this->fechaFin) {
            Log::info('Aplicando filtro de fechas...');
            $query->whereDate('created_at', '>=', $this->fechaInicio)
                  ->whereDate('created_at', '<=', $this->fechaFin);
        } else {
            Log::info('NO hay fechas - exportando TODOS');
        }

        $count = $query->count();
        Log::info('Registros encontrados:', ['count' => $count]);

        return $query->orderBy('created_at', 'desc')->get();
    }

        public function chunkSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        Log::info('=== GENERANDO HEADINGS ===');
        Log::info('Campos seleccionados:', [
            'campos' => $this->camposSeleccionados,
            'vacio' => empty($this->camposSeleccionados),
            'incluir_respuestas' => $this->incluirRespuestas
        ]);

        $allHeadings = [
            'id' => 'ID',
            'nombre' => 'Primer Nombre',
            'snombre' => 'Segundo Nombre',
            'apellido' => 'Primer Apellido',
            'sapellido' => 'Segundo Apellido',
            'nacimiento' => 'Fecha Nacimiento',
            'edad' => 'Edad',
            'genero' => 'Género',
            'telefono' => 'Teléfono',
            'colonia' => 'Colonia',
            'calle' => 'Calle',
            'numext' => 'Número Exterior',
            'numint' => 'Número Interior',
            'municipio' => 'Municipio',
            'cp' => 'Código Postal',
            'tarjeta_soluciones' => 'Tarjeta Soluciones',
            'duplicado' => 'Registro Duplicado',
            'fecha_registro' => 'Fecha de Registro',
            'hora_registro' => 'Hora de Registro',
        ];

        $headings = [];

        // Agregar los campos seleccionados
        if (!empty($this->camposSeleccionados)) {
            foreach ($this->camposSeleccionados as $campo) {
                if (isset($allHeadings[$campo])) {
                    $headings[] = $allHeadings[$campo];
                }
            }
        } else {
            $headings = array_values($allHeadings);
        }

        // ✅ SI INCLUYE RESPUESTAS, AGREGAR ENCABEZADOS DE LAS PREGUNTAS
        if ($this->incluirRespuestas) {
            Log::info('Cargando preguntas para headings...');
            
            // Cachear las preguntas para no consultarlas múltiples veces
            if ($this->preguntasCache === null) {
                $this->preguntasCache = \App\Models\Pregunta::where('activa', true)
                    ->orderBy('id')
                    ->get();
            }
            
            $preguntas = $this->preguntasCache;
            
            Log::info('Preguntas encontradas:', [
                'total' => $preguntas->count(),
                'preguntas' => $preguntas->pluck('descripcion')->toArray()
            ]);
            
            foreach ($preguntas as $pregunta) {
                // ✅ Usamos 'descripcion' que es el campo correcto según tu modelo
                $headings[] = $pregunta->descripcion;
            }
        }

        Log::info('Headings finales:', [
            'total' => count($headings),
            'headings' => $headings
        ]);

        return $headings;
    }

    public function map($beneficiario): array
    {
        $toNullString = function($value) {
            if ($value === null) return 'null';
            if (is_string($value) && trim($value) === '') return 'null';
            if (empty($value) && $value !== 0) return 'null';
            return $value;
        };

        // Array completo con TODOS los datos
        $allData = [
            'id' => $beneficiario->id !== null ? (string)$beneficiario->id : 'null',
            'nombre' => $toNullString($beneficiario->nombre),
            'snombre' => $toNullString($beneficiario->snombre),
            'apellido' => $toNullString($beneficiario->apellido),
            'sapellido' => $toNullString($beneficiario->sapellido),
            'nacimiento' => $toNullString($beneficiario->nacimiento),
            'edad' => $beneficiario->edad !== null ? (string)$beneficiario->edad : 'null',
            'genero' => $toNullString($beneficiario->genero),
            'telefono' => $toNullString($beneficiario->telefono),
            'colonia' => $toNullString($beneficiario->colonia),
            'calle' => $toNullString($beneficiario->calle),
            'numext' => $beneficiario->numext !== null ? (string)$beneficiario->numext : 'null',
            'numint' => $beneficiario->numint !== null ? (string)$beneficiario->numint : 'null',
            'municipio' => $toNullString($beneficiario->municipio),
            'cp' => $toNullString($beneficiario->cp),
            'tarjeta_soluciones' => $toNullString($beneficiario->tarjeta_soluciones),
            'duplicado' => $beneficiario->duplicado ? 'Sí' : 'No',
            'fecha_registro' => $beneficiario->created_at ? $beneficiario->created_at->format('d/m/Y') : 'null',
            'hora_registro' => $beneficiario->created_at ? $beneficiario->created_at->format('H:i:s') : 'null',
        ];

        // FILTRAR SOLO LOS CAMPOS SELECCIONADOS
        if (!empty($this->camposSeleccionados)) {
            $data = [];
            foreach ($this->camposSeleccionados as $campo) {
                $data[] = isset($allData[$campo]) ? (string)$allData[$campo] : 'null';
            }
        } else {
            $data = array_map('strval', array_values($allData));
        }

        // SI INCLUYE RESPUESTAS, AGREGAR LOS VALORES AL FINAL
        if ($this->incluirRespuestas) {
            // Usar el cache de preguntas o cargarlas
            if ($this->preguntasCache === null) {
                $this->preguntasCache = \App\Models\Pregunta::where('activa', true)
                    ->orderBy('id')
                    ->get();
            }
            
            $preguntas = $this->preguntasCache;
            $respuestasPorPregunta = [];
            
            foreach ($beneficiario->respuestas as $respuesta) {
                $respuestasPorPregunta[$respuesta->pregunta_id] = $respuesta;
            }
            
            foreach ($preguntas as $pregunta) {
                if (isset($respuestasPorPregunta[$pregunta->id])) {
                    $respuesta = $respuestasPorPregunta[$pregunta->id];
                    $valor = $respuesta->valor_extra ?? '';
                    if ($respuesta->detalle) {
                        $valor .= ': ' . $respuesta->detalle;
                    }
                    $data[] = $valor ?: 'null';
                } else {
                    $data[] = 'null';
                }
            }
        }

        return $data;
    }

public function registerEvents(): array
{
    return [
        AfterSheet::class => function(AfterSheet $event) {
            $sheet = $event->sheet;
            
            // Insertar 6 filas al inicio
            $sheet->insertNewRowBefore(1, 6);
            
            $ultimaColumna = $this->getLastColumnLetter();
            
            // === LOGO ===
            $logoPaths = [
                public_path('images/gobierno.webp'),
                public_path('images/gobierno.png'),
                public_path('images/gobierno.jpg'),
                public_path('images/logo.webp'),
                public_path('images/logo.png'),
            ];
            
            $logoEncontrado = null;
            foreach ($logoPaths as $path) {
                if (file_exists($path)) {
                    $logoEncontrado = $path;
                    break;
                }
            }
            
            if ($logoEncontrado) {
                try {
                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setPath($logoEncontrado);
                    $drawing->setHeight(60);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(5);
                    $drawing->setWorksheet($event->sheet->getDelegate());
                } catch (\Exception $e) {
                    // Si falla, continuar sin logo
                }
            }
            
            $columnaInicio = ($logoEncontrado && file_exists($logoEncontrado)) ? 'B' : 'A';
            
            // === TÍTULOS ===
            // Fila 1
            $sheet->mergeCells($columnaInicio . '1:' . $ultimaColumna . '1');
            $sheet->setCellValue($columnaInicio . '1', 'AGUASCALIENTES');
            $sheet->getStyle($columnaInicio . '1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            
            // Fila 2
            $sheet->mergeCells($columnaInicio . '2:' . $ultimaColumna . '2');
            $sheet->setCellValue($columnaInicio . '2', 'GOBIERNO DEL ESTADO');
            $sheet->getStyle($columnaInicio . '2')->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            
            // Fila 3 - CONSULTA POPULAR AGUASCALIENTES con subtítulo dinámico
            $sheet->mergeCells($columnaInicio . '3:' . $ultimaColumna . '3');
            
            // Determinar el subtítulo según si hay fechas o no
            if ($this->fechaInicio && $this->fechaFin) {
                $subtitulo = "REPORTE POR FECHAS";
            } else {
                $subtitulo = "REPORTE GENERAL";
            }
            
            // Título principal con subtítulo
            $tituloCompleto = "CONSULTA POPULAR AGUASCALIENTES\n" . $subtitulo;
            
            $sheet->setCellValue($columnaInicio . '3', $tituloCompleto);
            $sheet->getStyle($columnaInicio . '3')->applyFromArray([
                'font' => [
                    'bold' => true, 
                    'size' => 16, 
                    'color' => ['rgb' => '1FB7E9']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER, 
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true // Permite salto de línea
                ],
            ]);
            // Ajustar altura de la fila 3 para que quepa el texto en dos líneas
            $sheet->getRowDimension(3)->setRowHeight(45);
            
            // Fila 4 - Período
            if ($this->fechaInicio && $this->fechaFin) {
                $rangoTexto = "Período: {$this->fechaInicio} al {$this->fechaFin}";
            } else {
                $rangoTexto = "Período: Todos los registros";
            }
            $sheet->mergeCells($columnaInicio . '4:' . $ultimaColumna . '4');
            $sheet->setCellValue($columnaInicio . '4', $rangoTexto);
            $sheet->getStyle($columnaInicio . '4')->applyFromArray([
                'font' => ['italic' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            
            // Fila 5
            $sheet->mergeCells($columnaInicio . '5:' . $ultimaColumna . '5');
            $sheet->setCellValue($columnaInicio . '5', 'Generado el: ' . date('d/m/Y H:i:s'));
            $sheet->getStyle($columnaInicio . '5')->applyFromArray([
                'font' => ['italic' => true, 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            
            // === FILA 6 - ENCABEZADOS (AZUL, BLANCO, CENTRADO) ===
            $sheet->getStyle("A6:{$ultimaColumna}6")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1FB7E9']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            
            // === DATOS (ALINEACIÓN IZQUIERDA) ===
            $ultimaFila = $sheet->getHighestRow();
            if ($ultimaFila >= 7) {
                $sheet->getStyle("A7:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
            }
            
            // === BORDES ===
            if ($ultimaFila >= 6) {
                $sheet->getStyle("A6:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);
            }
            
            // === AJUSTES ===
            $sheet->getRowDimension(1)->setRowHeight(70);
            $sheet->getRowDimension(2)->setRowHeight(20);
            // La fila 3 ya tiene altura ajustada a 45
            $sheet->getRowDimension(4)->setRowHeight(18);
            $sheet->getRowDimension(5)->setRowHeight(18);
            
            if ($logoEncontrado && file_exists($logoEncontrado)) {
                $sheet->getColumnDimension('A')->setWidth(15);
            }
            
            // === CONGELAR ===
            $sheet->freezePane('A7');
        },
    ];
}
    
    private function getLastColumnLetter()
    {
        $headings = $this->headings();
        $columnCount = count($headings);
        if ($columnCount == 0) return 'A';
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);
    }
}