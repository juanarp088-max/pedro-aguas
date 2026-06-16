<?php

namespace App\Http\Controllers;

use App\Exports\BeneficiariosExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class ExportacionController extends Controller
{
    public function index()
    {
        try {
            return Inertia::render('export/index');
        } catch (\Exception $e) {
            Log::error('Error al cargar vista de exportación', [
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => 'Error al cargar la página de exportación']);
        }
    }

    public function exportByDate(Request $request)
    {
        try {
            $fechaInicio = $request->input('fecha_inicio');
            $fechaFin = $request->input('fecha_fin');
            $incluirRespuestas = $request->input('incluir_respuestas', false);
            $camposSeleccionados = $request->input('campos', []);

            // Convertir a booleano si es string
            if (is_string($incluirRespuestas)) {
                $incluirRespuestas = $incluirRespuestas === 'true' || $incluirRespuestas === '1';
            }

            Log::info('Exportación por rango de fechas', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'incluir_respuestas' => $incluirRespuestas,
                'campos' => $camposSeleccionados,
                'ip' => $request->ip()
            ]);

            // Si no hay campos seleccionados, enviar todos
            if (empty($camposSeleccionados)) {
                $camposSeleccionados = [
                    'id', 'nombre', 'snombre', 'apellido', 'sapellido',
                    'nacimiento', 'edad', 'genero', 'telefono', 'colonia',
                    'calle', 'numext', 'numint', 'municipio', 'cp',
                    'tarjeta_soluciones', 'duplicado', 'fecha_registro', 'hora_registro'
                ];
            }

            $export = new BeneficiariosExport([], $incluirRespuestas, $fechaInicio, $fechaFin, $camposSeleccionados);
            $nombreArchivo = 'beneficiarios_' . $fechaInicio . '_a_' . $fechaFin . '_' . date('Y-m-d_His') . '.xlsx';
            
            return Excel::download($export, $nombreArchivo);
            
        } catch (\Exception $e) {
            Log::error('Error al exportar por rango de fechas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip()
            ]);
            
            return back()->withErrors(['error' => 'Error al generar el archivo Excel: ' . $e->getMessage()]);
        }
    }

    public function exportAll(Request $request)
    {
        try {
            $incluirRespuestas = $request->boolean('incluir_respuestas', false);
            $camposSeleccionados = $request->input('campos', []);
            
            Log::info('Exportación completa de beneficiarios', [
                'incluir_respuestas' => $incluirRespuestas,
                'campos' => $camposSeleccionados,
                'ip' => $request->ip()
            ]);

            // Si no hay campos seleccionados, enviar todos
            if (empty($camposSeleccionados)) {
                $camposSeleccionados = [
                    'id', 'nombre', 'snombre', 'apellido', 'sapellido',
                    'nacimiento', 'edad', 'genero', 'telefono', 'colonia',
                    'calle', 'numext', 'numint', 'municipio', 'cp',
                    'tarjeta_soluciones', 'duplicado', 'fecha_registro', 'hora_registro'
                ];
            }
            
            $export = new BeneficiariosExport([], $incluirRespuestas, null, null, $camposSeleccionados);
            $nombreArchivo = 'beneficiarios_completos_' . date('Y-m-d_His') . '.xlsx';
            
            return Excel::download($export, $nombreArchivo);
            
        } catch (\Exception $e) {
            Log::error('Error al exportar todos los beneficiarios', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip()
            ]);
            
            return back()->withErrors(['error' => 'Error al generar el archivo Excel: ' . $e->getMessage()]);
        }
    }
}