<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permiso que asigna el Administrador al médico para marcar citas como atendidas (CU30, RF10).
 * Por defecto el médico no tiene el permiso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            $table->boolean('puede_marcar_atendida')->default(false)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            $table->dropColumn('puede_marcar_atendida');
        });
    }
};
