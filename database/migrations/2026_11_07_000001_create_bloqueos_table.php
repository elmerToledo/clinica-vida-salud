<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feriados y bloqueos de agenda (CU46).
 *
 * medico_id nulo = bloqueo de toda la clínica (feriado). Con médico = bloqueo solo de su agenda (vacaciones, licencia).
 * Cubre un rango de fechas (fecha_inicio a fecha_fin, ambas incluidas). Nunca se elimina: se desactiva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('motivo', 120);
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fecha_inicio', 'fecha_fin']);
            $table->index('medico_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE bloqueos ADD CONSTRAINT bloqueos_rango_valido CHECK (fecha_fin >= fecha_inicio)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos');
    }
};
