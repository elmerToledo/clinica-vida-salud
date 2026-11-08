<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago de una cita (CU49). Lo registra recepción (efectivo o tarjeta) o lo paga el paciente en línea con PayPal; no se elimina: se anula si la cita se cancela.
 */
class Pago extends Model
{
    public const MODULO_BITACORA = 'pagos';

    public const PAGADO = 'pagado';
    public const ANULADO = 'anulado';

    public const EFECTIVO = 'efectivo';
    public const TARJETA_CREDITO = 'tarjeta_credito';
    public const TARJETA_DEBITO = 'tarjeta_debito';
    public const PAYPAL = 'paypal';

    /** Método guardado => nombre para mostrar. */
    public const METODOS = [
        self::EFECTIVO => 'Efectivo',
        self::TARJETA_CREDITO => 'Tarjeta de crédito',
        self::TARJETA_DEBITO => 'Tarjeta de débito',
        self::PAYPAL => 'PayPal',
    ];

    /** Métodos que la recepcionista registra en ventanilla; PayPal lo paga el propio paciente en línea. */
    public const METODOS_RECEPCION = [
        self::EFECTIVO => 'Efectivo',
        self::TARJETA_CREDITO => 'Tarjeta de crédito',
        self::TARJETA_DEBITO => 'Tarjeta de débito',
    ];

    protected $table = 'pagos';

    protected $fillable = ['cita_id', 'monto', 'metodo', 'referencia', 'estado', 'pagado_at', 'registrado_por'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'pagado_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Código único legible (PAG-00001), generado a partir del id.
        static::created(function (Pago $pago) {
            if (! $pago->codigo) {
                $pago->forceFill(['codigo' => 'PAG-' . str_pad((string) $pago->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function getMetodoNombreAttribute(): string
    {
        return self::METODOS[$this->metodo] ?? (string) $this->metodo;
    }

    /** Los pagos con tarjeta o PayPal llevan el número de operación como referencia. */
    public static function exigeReferencia(string $metodo): bool
    {
        return $metodo !== self::EFECTIVO;
    }
}
