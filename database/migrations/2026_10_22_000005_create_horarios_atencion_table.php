<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Horarios de atención de cada médico (RF05, RN05, RN08).
 *
 * dia_semana sigue el estándar ISO: 1 = lunes ... 7 = domingo.
 * duracion_cita_min es la duración de cada cita en minutos (por ejemplo, 30).
 *
 * En PostgreSQL la propia base de datos impide:
 *  - horas inválidas (la hora final debe ser posterior a la inicial),
 *  - franjas donde no cabe ni una cita completa,
 *  - dos horarios activos del mismo médico que se superpongan el mismo día.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedSmallInteger('duracion_cita_min')->default(30);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['medico_id', 'dia_semana']);
        });

        if (DB::getDriverName() === 'pgsql') {
            // Necesaria para combinar igualdad (=) y superposición (&&) en una restricción de exclusión.
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

            DB::statement('ALTER TABLE horarios_atencion ADD CONSTRAINT horarios_dia_valido CHECK (dia_semana BETWEEN 1 AND 7)');
            DB::statement('ALTER TABLE horarios_atencion ADD CONSTRAINT horarios_hora_valida CHECK (hora_fin > hora_inicio)');
            DB::statement('ALTER TABLE horarios_atencion ADD CONSTRAINT horarios_duracion_valida CHECK (duracion_cita_min BETWEEN 5 AND 240)');
            DB::statement('ALTER TABLE horarios_atencion ADD CONSTRAINT horarios_cabe_una_cita CHECK (EXTRACT(EPOCH FROM (hora_fin - hora_inicio)) >= duracion_cita_min * 60)');

            // Dos horarios activos del mismo médico, el mismo día, no pueden superponerse.
            // Los tramos contiguos (08:00-12:00 y 12:00-14:00) sí están permitidos.
            DB::statement("ALTER TABLE horarios_atencion ADD CONSTRAINT horarios_sin_solape EXCLUDE USING gist (medico_id WITH =, dia_semana WITH =, tsrange(date '2000-01-01' + hora_inicio, date '2000-01-01' + hora_fin) WITH &&) WHERE (activo)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_atencion');
    }
};
