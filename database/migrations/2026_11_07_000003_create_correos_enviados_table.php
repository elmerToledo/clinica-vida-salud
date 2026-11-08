<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correos_enviados', function (Blueprint $table) {
            $table->id();
            $table->string('destinatario');
            $table->string('asunto');
            $table->text('contenido')->nullable();
            $table->string('estado', 20); // enviado | simulado | fallido
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index('created_at');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correos_enviados');
    }
};
