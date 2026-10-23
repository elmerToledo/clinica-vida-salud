<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Citas médicas (RF06 a RF12, RN03 a RN13).
 *
 * estado: programada, confirmada, atendida, cancelada o inasistencia.
 * llegada_at: momento en que se registró la asistencia del paciente (CU29).
 * Una cita nunca se elimina: se cancela y el horario queda libre (RN09, RN11).
 *
 * En PostgreSQL la propia base de datos impide:
 *  - estados inválidos y horas invertidas,
 *  - dos citas no canceladas del mismo médico que se superpongan (RN07),
 *    aunque dos personas reserven al mismo tiempo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->nullable()->unique();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('estado', 20)->default('programada');
            $table->text('motivo_consulta')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('llegada_at')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['medico_id', 'fecha']);
            $table->index(['paciente_id', 'fecha']);
            $table->index('estado');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

            DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_estado_valido CHECK (estado IN ('programada','confirmada','atendida','cancelada','inasistencia'))");
            DB::statement('ALTER TABLE citas ADD CONSTRAINT citas_hora_valida CHECK (hora_fin > hora_inicio)');

            // Un médico no puede tener dos citas no canceladas superpuestas (RN07).
            // Las citas contiguas (08:00-08:30 y 08:30-09:00) sí están permitidas.
            DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_sin_solape EXCLUDE USING gist (medico_id WITH =, tsrange(fecha + hora_inicio, fecha + hora_fin) WITH &&) WHERE (estado <> 'cancelada')");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
