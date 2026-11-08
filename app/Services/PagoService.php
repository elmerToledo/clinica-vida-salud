<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Cobro de las citas (CU49). Registra el pago; PayPal es una simulación sin pasarela real.
 */
class PagoService
{
    /** ¿Se puede cobrar esta cita? No si está cancelada, no asistió o ya fue pagada. */
    public function puedeCobrarse(Cita $cita): bool
    {
        return in_array($cita->estado, [Cita::PROGRAMADA, Cita::CONFIRMADA, Cita::ATENDIDA], true)
            && $cita->pago?->estado !== Pago::PAGADO;
    }

    /**
     * Registra el pago de una cita. El monto es el precio de la especialidad de la cita.
     *
     * @param array{metodo: string, referencia?: ?string} $datos
     */
    public function registrar(Cita $cita, array $datos, ?User $por = null): Pago
    {
        return DB::transaction(function () use ($cita, $datos, $por) {
            // Se vuelve a leer la cita bloqueada para que dos cobros simultáneos no se dupliquen.
            $cita = Cita::query()->whereKey($cita->id)->lockForUpdate()->firstOrFail();
            $cita->load(['especialidad', 'pago']);

            if (in_array($cita->estado, [Cita::CANCELADA, Cita::INASISTENCIA], true)) {
                throw ValidationException::withMessages(['metodo' => 'No se cobra una cita ' . mb_strtolower($cita->estado_nombre) . '.']);
            }

            if ($cita->pago?->estado === Pago::PAGADO) {
                throw ValidationException::withMessages(['metodo' => 'Esta cita ya fue cobrada.']);
            }

            $precio = (float) $cita->especialidad->precio;
            if ($precio <= 0) {
                throw ValidationException::withMessages(['metodo' => 'La especialidad no tiene un precio definido. Pida al administrador que lo registre.']);
            }

            $referencia = isset($datos['referencia']) ? trim((string) $datos['referencia']) : null;
            if (Pago::exigeReferencia($datos['metodo']) && ($referencia === null || $referencia === '')) {
                throw ValidationException::withMessages(['referencia' => 'Escriba el número de operación o de comprobante del pago.']);
            }

            $pago = Pago::create([
                'cita_id' => $cita->id,
                'monto' => $precio,
                'metodo' => $datos['metodo'],
                'referencia' => $referencia ?: null,
                'estado' => Pago::PAGADO,
                'pagado_at' => now(),
                'registrado_por' => $por?->id,
            ]);

            Bitacora::registrar('cobrar', Pago::MODULO_BITACORA, $pago->id,
                "{$pago->codigo} · {$cita->codigo} · Bs {$pago->monto} · {$pago->metodo_nombre}");

            return $pago;
        });
    }

    /**
     * El paciente paga su cita con PayPal. Es una simulación: no se conecta a PayPal ni mueve dinero;
     * genera el número de operación como lo haría la pasarela y registra el pago.
     */
    public function pagarEnLinea(Cita $cita, User $paciente): Pago
    {
        $referencia = 'PP-' . strtoupper(Str::random(10));

        return $this->registrar($cita, ['metodo' => Pago::PAYPAL, 'referencia' => $referencia], $paciente);
    }

    /** Anula el pago de una cita que se cancela (el reembolso se hace en recepción). */
    public function anular(Cita $cita): void
    {
        $pago = $cita->pago;

        if ($pago && $pago->estado === Pago::PAGADO) {
            $pago->update(['estado' => Pago::ANULADO]);
            Bitacora::registrar('anular', Pago::MODULO_BITACORA, $pago->id, "{$pago->codigo} · {$cita->codigo} · Bs {$pago->monto}");
        }
    }
}
