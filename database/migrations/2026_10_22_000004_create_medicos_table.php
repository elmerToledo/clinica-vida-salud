<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de médicos y de su relación con las especialidades (RF03, RN02, RN11).
 *
 * - Un médico puede tener varias especialidades y una especialidad varios médicos
 *   (tabla intermedia "especialidad_medico").
 * - Una especialidad asociada a médicos no puede borrarse (restrictOnDelete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('registro_profesional', 30)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('telefono', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['apellidos', 'nombres']);
        });

        Schema::create('especialidad_medico', function (Blueprint $table) {
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->timestamps();

            $table->primary(['medico_id', 'especialidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidad_medico');
        Schema::dropIfExists('medicos');
    }
};
