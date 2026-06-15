<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpcionPregunta extends Model
{
    use HasFactory;

    protected $table = 'opciones_preguntas';

    protected $fillable = [
        'pregunta_id',
        'opcion',
        'valor',
        'requiere_especificar',
        'orden',
    ];

    protected $casts = [
        'requiere_especificar' => 'boolean',
    ];

    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class);
    }
}