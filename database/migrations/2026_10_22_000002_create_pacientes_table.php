<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de pacientes (RF02, RN01, RN11).
 *
 * - El CI es único cuando existe (puede ser nulo).
 * - Nunca se elimina un paciente con historial: se desactiva (campo "activo").
 * - user_id enlaza al paciente con su cuenta del portal (opcional).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('ci', 20)->nullable()->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->date('fecha_nacimiento');
            $table->string('sexo', 1)->nullable();
            $table->string('telefono', 20);
            $table->string('email', 150)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['apellidos', 'nombres']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
