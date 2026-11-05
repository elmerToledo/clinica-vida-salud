<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cobro de las citas (CU49). Solo se registra el pago; no hay pasarela de pago real.
 *
 * metodo: efectivo, tarjeta_credito, tarjeta_debito o paypal.
 * estado: pagado o anulado (el pago se anula si la cita se cancela).
 * Cada cita tiene como máximo un pago (cita_id único). Un pago nunca se elimina.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->nullable()->unique();
            $table->foreignId('cita_id')->unique()->constrained('citas')->restrictOnDelete();
            $table->decimal('monto', 8, 2);
            $table->string('metodo', 20);
            $table->string('referencia', 40)->nullable();
            $table->string('estado', 10)->default('pagado');
            $table->timestamp('pagado_at');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('metodo');
            $table->index('estado');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE pagos ADD CONSTRAINT pagos_metodo_valido CHECK (metodo IN ('efectivo','tarjeta_credito','tarjeta_debito','paypal'))");
            DB::statement("ALTER TABLE pagos ADD CONSTRAINT pagos_estado_valido CHECK (estado IN ('pagado','anulado'))");
            DB::statement('ALTER TABLE pagos ADD CONSTRAINT pagos_monto_valido CHECK (monto > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
