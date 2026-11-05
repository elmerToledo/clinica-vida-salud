<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha de atención (CU48) y precio de la consulta (CU49).
 *
 * ficha: número de turno de la cita para un médico y una fecha (1, 2, 3...).
 *   Se libera (queda en NULL) cuando la cita se cancela o el paciente no se presenta,
 *   y el número se reutiliza en la siguiente cita. El índice único parcial impide
 *   que dos citas con ficha repitan el número para el mismo médico y fecha.
 * precio: costo de la consulta de la especialidad, en bolivianos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->unsignedSmallInteger('ficha')->nullable()->after('hora_fin');
        });

        Schema::table('especialidades', function (Blueprint $table) {
            $table->decimal('precio', 8, 2)->default(0)->after('descripcion');
        });

        // Las especialidades existentes parten de un precio base que el administrador puede modificar.
        DB::table('especialidades')->where('precio', 0)->update(['precio' => 100]);

        // Las citas existentes reciben su ficha por orden de registro, por médico y fecha.
        $contadores = [];
        DB::table('citas')
            ->whereNotIn('estado', ['cancelada', 'inasistencia'])
            ->orderBy('medico_id')->orderBy('fecha')->orderBy('id')
            ->get(['id', 'medico_id', 'fecha'])
            ->each(function ($c) use (&$contadores) {
                $clave = $c->medico_id . '|' . $c->fecha;
                $contadores[$clave] = ($contadores[$clave] ?? 0) + 1;
                DB::table('citas')->where('id', $c->id)->update(['ficha' => $contadores[$clave]]);
            });

        DB::statement('CREATE UNIQUE INDEX citas_ficha_unica ON citas (medico_id, fecha, ficha) WHERE ficha IS NOT NULL');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE citas ADD CONSTRAINT citas_ficha_valida CHECK (ficha IS NULL OR ficha > 0)');
            DB::statement('ALTER TABLE especialidades ADD CONSTRAINT especialidades_precio_valido CHECK (precio >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_ficha_valida');
            DB::statement('ALTER TABLE especialidades DROP CONSTRAINT IF EXISTS especialidades_precio_valido');
        }

        DB::statement('DROP INDEX IF EXISTS citas_ficha_unica');

        Schema::table('citas', fn (Blueprint $table) => $table->dropColumn('ficha'));
        Schema::table('especialidades', fn (Blueprint $table) => $table->dropColumn('precio'));
    }
};
