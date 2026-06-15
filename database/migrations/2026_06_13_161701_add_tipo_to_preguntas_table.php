<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->enum('tipo', ['simple', 'multiple'])->default('simple')->after('descripcion');
        });
    }

    public function down()
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};