<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega a la tabla de usuarios el rol (RF01, RN12) y el estado activo/inactivo.
 *
 * Roles permitidos: administrador, recepcionista, medico, paciente.
 * Los usuarios que se registran desde la pantalla pública de Breeze
 * quedan como "paciente" por defecto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('paciente')->index();
            $table->boolean('activo')->default(true);
        });

        // En PostgreSQL se protege el dominio del rol también desde la base de datos.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_check CHECK (rol IN ('administrador', 'recepcionista', 'medico', 'paciente'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_check');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rol', 'activo']);
        });
    }
};
