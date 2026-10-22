<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de especialidades médicas (RF04).
 *
 * "nombre_normalizado" guarda el nombre sin tildes, en minúsculas y sin
 * espacios repetidos. Al ser único evita registrar dos especialidades
 * con el mismo nombre normalizado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('nombre_normalizado', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidades');
    }
};
