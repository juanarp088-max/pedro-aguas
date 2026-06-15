<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('opciones_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained()->onDelete('cascade');
            $table->string('opcion');
            $table->string('valor', 5)->nullable();
            $table->boolean('requiere_especificar')->default(false);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('opciones_preguntas');
    }
};