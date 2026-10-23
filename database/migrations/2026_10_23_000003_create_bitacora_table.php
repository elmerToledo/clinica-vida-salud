<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora del sistema: registro de quién hizo qué y cuándo. La consulta es solo del administrador.
 *
 * - user_id puede ser nulo (por ejemplo, un intento de ingreso fallido de alguien que no existe);
 *   en ese caso "usuario" guarda el nombre que se escribió.
 * - Es de solo escritura: en PostgreSQL un disparador impide modificar o eliminar registros (RN11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('usuario', 50)->nullable();
            $table->string('accion', 30);
            $table->string('modulo', 30);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->text('detalle')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('fecha_hora')->useCurrent();

            $table->index('fecha_hora');
            $table->index(['user_id', 'fecha_hora']);
            $table->index(['modulo', 'accion']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE FUNCTION bitacora_inmutable() RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'La bitácora no se puede modificar ni eliminar';
                END;
                $$ LANGUAGE plpgsql
            SQL);

            DB::unprepared('CREATE TRIGGER bitacora_sin_cambios BEFORE UPDATE OR DELETE ON bitacora FOR EACH ROW EXECUTE FUNCTION bitacora_inmutable()');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS bitacora_inmutable()');
        }
    }
};
