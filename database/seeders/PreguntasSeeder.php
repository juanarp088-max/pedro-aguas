<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pregunta;
use App\Models\OpcionPregunta;

class PreguntasSeeder extends Seeder
{
    public function run()
    {
        // Pregunta múltiple - Tipo de vivienda
        $pregunta1 = Pregunta::create([
            'descripcion' => '¿CUÁL ES EL TIPO DE VIVIENDA?',
            'activa' => true,
            'tipo' => 'multiple'
        ]);
        
        OpcionPregunta::create([
            'pregunta_id' => $pregunta1->id,
            'opcion' => 'PROPIA',
            'valor' => 'a',
            'requiere_especificar' => false,
            'orden' => 1
        ]);
        
        OpcionPregunta::create([
            'pregunta_id' => $pregunta1->id,
            'opcion' => 'RENTADA',
            'valor' => 'b',
            'requiere_especificar' => false,
            'orden' => 2
        ]);
        
        OpcionPregunta::create([
            'pregunta_id' => $pregunta1->id,
            'opcion' => 'FAMILIAR',
            'valor' => 'c',
            'requiere_especificar' => false,
            'orden' => 3
        ]);
        
        OpcionPregunta::create([
            'pregunta_id' => $pregunta1->id,
            'opcion' => 'PRESTADA',
            'valor' => 'd',
            'requiere_especificar' => false,
            'orden' => 4
        ]);
        
        OpcionPregunta::create([
            'pregunta_id' => $pregunta1->id,
            'opcion' => 'OTRA',
            'valor' => 'e',
            'requiere_especificar' => true,
            'orden' => 5
        ]);

        // Pregunta múltiple - Nivel de estudios
        $pregunta2 = Pregunta::create([
            'descripcion' => '¿CUÁL ES SU NIVEL DE ESTUDIOS?',
            'activa' => true,
            'tipo' => 'multiple'
        ]);
        
        $opciones = ['PRIMARIA', 'SECUNDARIA', 'BACHILLERATO', 'LICENCIATURA', 'OTRO/S'];
        foreach ($opciones as $index => $opcion) {
            OpcionPregunta::create([
                'pregunta_id' => $pregunta2->id,
                'opcion' => $opcion,
                'valor' => chr(97 + $index),
                'requiere_especificar' => $opcion === 'OTRO/S',
                'orden' => $index + 1
            ]);
        }

        // Pregunta simple (Sí/No)
        Pregunta::create([
            'descripcion' => '¿FORMA PARTE DE ALGÚN CLUB CULTURAL O DEPORTIVO?',
            'activa' => true,
            'tipo' => 'simple'
        ]);
    }
}