<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recordatorios de cita (CU43): tabla de notificaciones de Laravel (la campanita del paciente)
 * y la marca de cuándo se envió el recordatorio de cada cita, para no enviarlo dos veces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->timestamp('recordatorio_enviado_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn('recordatorio_enviado_at');
        });
        Schema::dropIfExists('notifications');
    }
};
