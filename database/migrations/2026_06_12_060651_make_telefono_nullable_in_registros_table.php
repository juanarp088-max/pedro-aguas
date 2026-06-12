<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('registros', function (Blueprint $table) {
            // 1. Modificar tipos (esto no falla si ya son varchar)
            $table->string('numext', 10)->nullable()->change();
            $table->string('numint', 10)->nullable()->change();
            
            // 2. Modificar teléfono a nullable
            $table->string('telefono', 15)->nullable()->change();
            
            // 3. Solo agregar si NO existe
            if (!Schema::hasColumn('registros', 'tarjeta_soluciones')) {
                $table->string('tarjeta_soluciones', 7)->nullable();
            }
            
            // 4. Solo agregar si NO existe
            if (!Schema::hasColumn('registros', 'id_old')) {
                $table->integer('id_old')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('registros', function (Blueprint $table) {
            // Revertir teléfono a no nullable (asumiendo que originalmente era NOT NULL)
            $table->string('telefono', 20)->nullable(false)->change();
            
            $table->dropColumn(['tarjeta_soluciones', 'id_old']);
        });
    }
};