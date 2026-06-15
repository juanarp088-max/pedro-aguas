<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    use HasFactory;

    protected $table = 'preguntas';
    protected $with = ['opciones']; // Esto carga automáticamente las opciones


    protected $fillable = [
        'descripcion',
        'activa',
        'tipo',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function respuestas()
    {
        return $this->hasMany(Respuesta::class, 'pregunta_id');
    }

    public function catalogos()
    {
        return $this->hasMany(Catalogo::class, 'pregunta_id');
    }

// app/Models/Pregunta.php
public function opciones()
{
    return $this->hasMany(OpcionPregunta::class, 'pregunta_id')->orderBy('orden');
}
}