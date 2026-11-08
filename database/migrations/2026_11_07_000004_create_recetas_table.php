<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 12)->nullable()->unique();
            $table->foreignId('cita_id')->unique()->constrained('citas')->cascadeOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->text('diagnostico');
            $table->text('medicamentos');
            $table->text('indicaciones')->nullable();
            $table->boolean('publicada')->default(false);
            $table->timestamp('publicada_at')->nullable();
            $table->timestamps();
            $table->index('paciente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recetas');
    }
};
