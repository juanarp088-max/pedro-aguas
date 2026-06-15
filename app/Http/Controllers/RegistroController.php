<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Registro;
use App\Models\Pregunta;
use App\Models\Catalogo;
use App\Models\OpcionPregunta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RegistroController extends Controller
{
    /**
     * Reglas de validación compartidas
     */
    private function getReglasValidacion()
    {
        return [
            'nombre'     => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s]+$/u'],
            'snombre'    => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\s]+$/u'],
            'apellido'   => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s]+$/u'],
            'sapellido'  => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\s]+$/u'],
            'colonia'    => ['required', 'string', 'exists:colonias,nombre_de_la_colonia'],
            'calle'      => ['required', 'string', 'exists:calles_aguascalientes,nombre'],
            'municipio'  => ['required', 'string', 'exists:colonias,nombre_del_municipio'],
            'cp'         => ['nullable', 'numeric', 'digits:5'],
            'genero'     => ['required', 'string', Rule::in(['M', 'F', 'OTRO'])],
            'edad'       => ['required', 'numeric', 'min:0', 'max:120'],
            'nacimiento' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
            'numext'     => ['required', 'numeric', 'min:1'],
            'numint'     => ['nullable', 'numeric', 'min:1'],
            'telefono'   => ['required', 'digits:10'],
            'respuestas' => ['nullable', 'array'],
            'respuestas_multiple' => ['nullable', 'array'],
        ];
    }

    /**
     * Mensajes personalizados
     */
    private function getMensajesPersonalizados()
    {
        return [
            'nombre.regex'               => 'El nombre solo puede contener letras y espacios.',
            'apellido.regex'             => 'El apellido solo puede contener letras y espacios.',
            'snombre.regex'              => 'El segundo nombre solo puede contener letras y espacios.',
            'sapellido.regex'            => 'El segundo apellido solo puede contener letras y espacios.',
            'colonia.exists'             => 'La colonia no existe en nuestros registros.',
            'calle.exists'               => 'La calle seleccionada no existe.',
            'municipio.exists'           => 'El municipio seleccionado no existe.',
            'telefono.digits'            => 'El teléfono debe tener exactamente 10 dígitos.',
            'nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'nacimiento.after_or_equal'  => 'La fecha de nacimiento no es válida.',
            'edad.min'                   => 'La edad debe ser un número positivo.',
            'edad.max'                   => 'La edad no puede ser mayor a 120 años.',
            'genero.in'                  => 'El género seleccionado no es válido.',
            'required'                   => 'El campo :attribute es obligatorio.',
            'numeric'                    => 'El campo :attribute debe ser numérico.',
            'digits'                     => 'El campo :attribute debe tener exactamente :digits dígitos.',
            'cp.digits'                  => 'El código postal debe tener 5 dígitos.',
            'numext.min'                 => 'El número exterior debe ser mayor a 0.',
            'numint.min'                 => 'El número interior debe ser mayor a 0.',
        ];
    }

    /**
     * Limpiar tildes de una cadena
     */
    private function limpiarTildes($cadena)
    {
        if (!is_string($cadena)) {
            return $cadena;
        }
        
        $mapeo = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'ñ' => 'ñ', 'Ñ' => 'Ñ'
        ];
        
        return strtr($cadena, $mapeo);
    }

    /**
     * Limpiar y preparar los datos del request
     */
    private function limpiarYPrepararDatos(Request $request)
    {
        $camposTexto = ['nombre', 'snombre', 'apellido', 'sapellido', 'colonia', 'calle', 'municipio'];

        foreach ($camposTexto as $campo) {
            if ($request->has($campo) && is_string($request->$campo)) {
                $request->merge([$campo => $this->limpiarTildes($request->$campo)]);
            }
        }

        if ($request->has('genero') && is_string($request->genero)) {
            $genero = mb_strtoupper(trim($request->genero), 'UTF-8');
            $genero = match($genero) {
                'MASCULINO', 'M' => 'M',
                'FEMENINO', 'F' => 'F',
                'OTRO', 'O' => 'OTRO',
                default => $genero
            };
            $request->merge(['genero' => $genero]);
        }

        return $request;
    }

    /**
     * Formatear teléfono
     */
    private function formatearTelefono($telefono)
    {
        if (!$telefono) {
            return null;
        }

        $numeros = preg_replace('/[^0-9]/', '', $telefono);

        if (strlen($numeros) === 10) {
            return preg_replace('/(\d{3})(\d{3})(\d{4})/', '$1 $2 $3', $numeros);
        }

        return $numeros;
    }

    /**
     * Convertir datos a mayúsculas
     */
    private function convertirMayusculas(array $data, array $excluir = ['nacimiento', 'edad', 'telefono'])
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $excluir)) {
                continue;
            }

            if (is_string($value)) {
                $data[$key] = mb_strtoupper(trim($value), 'UTF-8');
            }
        }

        return $data;
    }

    /**
     * Detectar duplicados
     */
    private function detectarDuplicados($datos, $excluirId = null)
    {
        $nombre = mb_strtoupper(trim($datos['nombre']), 'UTF-8');
        $apellido = mb_strtoupper(trim($datos['apellido']), 'UTF-8');
        $nacimiento = $datos['nacimiento'];

        $query = Registro::whereRaw('UPPER(nombre) = ?', [$nombre])
            ->whereRaw('UPPER(apellido) = ?', [$apellido])
            ->where('nacimiento', $nacimiento);

        if ($excluirId) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->get();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Registro::query();

            if ($request->filled('nombre')) {
                $nombre = mb_strtoupper($request->input('nombre'), 'UTF-8');
                $query->where('nombre', 'LIKE', "%{$nombre}%");
            }

            if ($request->filled('snombre')) {
                $snombre = mb_strtoupper($request->input('snombre'), 'UTF-8');
                $query->where('snombre', 'LIKE', "%{$snombre}%");
            }

            if ($request->filled('apellido')) {
                $apellido = mb_strtoupper($request->input('apellido'), 'UTF-8');
                $query->where('apellido', 'LIKE', "%{$apellido}%");
            }

            if ($request->filled('sapellido')) {
                $sapellido = mb_strtoupper($request->input('sapellido'), 'UTF-8');
                $query->where('sapellido', 'LIKE', "%{$sapellido}%");
            }

            if ($request->filled('nacimiento')) {
                $query->where('nacimiento', $request->input('nacimiento'));
            }

            $beneficiarios = $query->orderBy('id', 'desc')->get();
            
            $preguntas = Pregunta::with('opciones')->where('activa', true)->orderBy('id')->get();

            return Inertia::render('consulta', [
                'beneficiarios' => $beneficiarios,
                'preguntas'     => $preguntas,
                'filtros'       => $request->only(['nombre', 'snombre', 'apellido', 'sapellido', 'nacimiento'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error en index Registro: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al cargar los datos']);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $preguntas = Pregunta::with('opciones')
                ->where('activa', true)
                ->orderBy('id')
                ->get();
            
            return Inertia::render('create', ['preguntas' => $preguntas]);
        } catch (\Exception $e) {
            Log::error('Error en create Registro: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al cargar el formulario']);
        }
    }

    /**
     * Display the specified resource (para edición).
     */
    public function show(string $id)
    {
        try {
            $beneficiario = Registro::with(['respuestas.catalogo'])->findOrFail($id);
            
            $respuestasFormateadas = [];
            $respuestasMultipleFormateadas = [];
            
            foreach ($beneficiario->respuestas as $respuesta) {
                $pregunta = Pregunta::with('opciones')->find($respuesta->pregunta_id);
                
                if ($pregunta && $pregunta->tipo === 'simple') {
                    $respuestasFormateadas[$respuesta->pregunta_id] = $respuesta->valor_extra;
                } 
                else if ($pregunta && $pregunta->tipo === 'multiple') {
                    $opcion = $pregunta->opciones->first(function ($op) use ($respuesta) {
                        return $op->opcion === $respuesta->valor_extra;
                    });
                    
                    if ($opcion) {
                        $respuestasMultipleFormateadas[$respuesta->pregunta_id] = [
                            'opcion_id' => $opcion->id,
                            'especificacion' => $respuesta->detalle ?? ''
                        ];
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'beneficiario' => $beneficiario,
                'respuestas' => $respuestasFormateadas,
                'respuestas_multiple' => $respuestasMultipleFormateadas
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener beneficiario para editar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los datos del beneficiario'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    $fuerzaBruta = $request->boolean('duplicado');

    $request = $this->limpiarYPrepararDatos($request);
    
    if ($request->has('telefono')) {
        $request->merge(['telefono' => str_replace(' ', '', $request->telefono)]);
    }

    $datosValidados = $request->validate(
        $this->getReglasValidacion(),
        $this->getMensajesPersonalizados()
    );

    $respuestas = $datosValidados['respuestas'] ?? [];
    $respuestasMultiple = $request->input('respuestas_multiple', []);

    $datosParaCrear = $datosValidados;
    unset($datosParaCrear['respuestas'], $datosParaCrear['respuestas_multiple']);

    if (isset($datosParaCrear['telefono'])) {
        $datosParaCrear['telefono'] = $this->formatearTelefono($datosParaCrear['telefono']);
    }

    // Verificación de duplicados
    $duplicados = null;
    if (!$fuerzaBruta) {
        $duplicados = $this->detectarDuplicados($datosParaCrear);
        if ($duplicados->isNotEmpty()) {
            return back()->with([
                'advertencia' => 'Ya existen registros similares.',
                'coincidencias' => $duplicados
            ]);
        }
    }

    $datosFinales = $this->convertirMayusculas($datosParaCrear);
    $datosFinales['id_user'] = Auth::id();
    $datosFinales['duplicado'] = $fuerzaBruta; // ← GUARDAR SI FUE FORZADO
    
    if (is_null($datosFinales['sapellido'] ?? null)) {
        $datosFinales['papa'] = true;
    }

    DB::beginTransaction();
    
    try {
        $nuevoRegistro = Registro::create($datosFinales);

        // Procesar preguntas simples
        foreach ($respuestas as $preguntaId => $valorRespuesta) {
            $valorNormalizado = mb_strtoupper(trim($valorRespuesta), 'UTF-8');
            
            $nuevoRegistro->respuestas()->create([
                'pregunta_id' => (int)$preguntaId,
                'catalogo_id' => null,
                'valor_extra' => $valorNormalizado,
                'detalle'     => null,
            ]);
        }

        // Procesar preguntas múltiples
        foreach ($respuestasMultiple as $preguntaId => $respuestaData) {
            $opcionId = $respuestaData['opcion_id'] ?? null;
            $especificacion = $respuestaData['especificacion'] ?? '';
            
            if ($opcionId) {
                $opcion = OpcionPregunta::with('pregunta')->find($opcionId);
                
                if ($opcion) {
                    $detalleOpcion = $opcion->opcion;
                    
                    if ($especificacion && trim($especificacion) !== '') {
                        $especificacionUpper = mb_strtoupper(trim($especificacion), 'UTF-8');
                        $detalleOpcion .= ': ' . $especificacionUpper;
                    }
                    
                    $catalogo = Catalogo::firstOrCreate([
                        'pregunta_id' => $preguntaId,
                        'nombre'      => $detalleOpcion
                    ]);
                    
                    $nuevoRegistro->respuestas()->create([
                        'pregunta_id' => (int)$preguntaId,
                        'catalogo_id' => $catalogo->id,
                        'valor_extra' => $opcion->opcion,
                        'detalle'     => $especificacion ? mb_strtoupper(trim($especificacion), 'UTF-8') : null,
                    ]);
                }
            }
        }

        DB::commit();

        Log::info('Registro creado exitosamente', [
            'id' => $nuevoRegistro->id,
            'usuario' => Auth::id(),
            'fuerza_bruta' => $fuerzaBruta,
            'ip' => $request->ip()
        ]);

        // Si fue forzado, redirigir a dashboard con mensaje específico
        if ($fuerzaBruta) {
            return redirect()->route('dashboard')->with('success', 'Registro guardado forzosamente (ignorando duplicados).');
        }

        return redirect()->route('dashboard')->with('success', 'Registro completado con éxito!');
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error en store Registro: ' . $e->getMessage());
        return back()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
    }
}

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, string $id)
{
    $beneficiario = Registro::findOrFail($id);

    $request = $this->limpiarYPrepararDatos($request);

    if ($request->has('telefono')) {
        $request->merge(['telefono' => str_replace(' ', '', $request->telefono)]);
    }

    $datosValidados = $request->validate(
        $this->getReglasValidacion(),
        $this->getMensajesPersonalizados()
    );

    $respuestas = $datosValidados['respuestas'] ?? [];
    $respuestasMultiple = $request->input('respuestas_multiple', []);
    
    unset($datosValidados['respuestas']);
    unset($datosValidados['respuestas_multiple']);

    // Verificar duplicados ANTES de actualizar
    $duplicados = $this->detectarDuplicados($datosValidados, $id);
    if ($duplicados->isNotEmpty()) {
        // Construir mensaje con los datos del duplicado
        $primerDuplicado = $duplicados->first();
        $mensaje = "No se puede actualizar porque ya existe un registro con estos datos:\n\n";
        $mensaje .= "• Nombre: " . $primerDuplicado->nombre . " " . ($primerDuplicado->snombre ?? '') . "\n";
        $mensaje .= "• Apellido: " . $primerDuplicado->apellido . " " . ($primerDuplicado->sapellido ?? '') . "\n";
        $mensaje .= "• Fecha de nacimiento: " . $primerDuplicado->nacimiento . "\n";
        $mensaje .= "\nID del registro existente: " . $primerDuplicado->id;
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $mensaje,
                'duplicados' => $duplicados
            ], 409);
        }
        
        return back()->withErrors([
            'duplicado' => $mensaje
        ])->withInput();
    }

    if (isset($datosValidados['telefono'])) {
        $datosValidados['telefono'] = $this->formatearTelefono($datosValidados['telefono']);
    }

    $datosFinales = $this->convertirMayusculas($datosValidados);
    
    if (is_null($datosFinales['sapellido'] ?? null)) {
        $datosFinales['papa'] = true;
    }
    
    $datosFinales['id_user'] = Auth::id();

    DB::beginTransaction();

    try {
        $beneficiario->update($datosFinales);
        $beneficiario->respuestas()->delete();
        
        // Procesar preguntas simples
        foreach ($respuestas as $preguntaId => $valorRespuesta) {
            $valorNormalizado = mb_strtoupper(trim($valorRespuesta), 'UTF-8');
            
            $beneficiario->respuestas()->create([
                'pregunta_id' => (int)$preguntaId,
                'catalogo_id' => null,
                'valor_extra' => $valorNormalizado,
                'detalle'     => null,
            ]);
        }

        // Procesar preguntas múltiples
        foreach ($respuestasMultiple as $preguntaId => $respuestaData) {
            $opcionId = $respuestaData['opcion_id'] ?? null;
            $especificacion = $respuestaData['especificacion'] ?? '';
            
            if ($opcionId) {
                $opcion = OpcionPregunta::with('pregunta')->find($opcionId);
                
                if ($opcion) {
                    $detalleOpcion = $opcion->opcion;
                    
                    if ($especificacion && trim($especificacion) !== '') {
                        $especificacionUpper = mb_strtoupper(trim($especificacion), 'UTF-8');
                        $detalleOpcion .= ': ' . $especificacionUpper;
                    }
                    
                    $catalogo = Catalogo::firstOrCreate([
                        'pregunta_id' => $preguntaId,
                        'nombre'      => $detalleOpcion
                    ]);
                    
                    $beneficiario->respuestas()->create([
                        'pregunta_id' => (int)$preguntaId,
                        'catalogo_id' => $catalogo->id,
                        'valor_extra' => $opcion->opcion,
                        'detalle'     => $especificacion ? mb_strtoupper(trim($especificacion), 'UTF-8') : null,
                    ]);
                }
            }
        }

        DB::commit();

        Log::info('Registro actualizado con éxito', [
            'id' => $id,
            'usuario' => Auth::id(),
            'ip' => $request->ip()
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado con éxito.'
            ]);
        }

        return redirect()->back()->with('success', 'Registro actualizado de forma correcta.');
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error al actualizar registro: ' . $e->getMessage());

        if ($request->wantsJson()) {
            return response()->json([
                'errors' => ['error' => 'Error al actualizar: ' . $e->getMessage()]
            ], 500);
        }

        return redirect()->back()->withErrors([
            'error' => 'Ocurrió un error interno al procesar la actualización.'
        ]);
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $beneficiario = Registro::findOrFail($id);

        DB::beginTransaction();

        try {
            $beneficiario->respuestas()->delete();
            $beneficiario->beneficios()->detach();
            $beneficiario->delete();

            DB::commit();

            Log::info('Registro eliminado con éxito', [
                'id' => $id,
                'usuario' => Auth::id()
            ]);

            return redirect()->back()->with('success', 'El registro ha sido eliminado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar registro: ' . $e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'No se pudo eliminar el registro: ' . $e->getMessage()
            ]);
        }
    }
}