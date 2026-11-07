<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feriado o bloqueo de agenda (CU46). Sin médico bloquea a toda la clínica.
 */
class Bloqueo extends Model
{
    public const MODULO_BITACORA = 'bloqueos';

    protected $table = 'bloqueos';

    protected $fillable = ['medico_id', 'fecha_inicio', 'fecha_fin', 'motivo', 'activo', 'creado_por'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'activo' => 'boolean',
        ];
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    /** Bloqueos activos que cubren una fecha para un médico (los de toda la clínica cuentan siempre). */
    public function scopeQueCubren(Builder $query, int $medicoId, string $fecha): Builder
    {
        return $query->where('activo', true)
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->where(fn ($q) => $q->whereNull('medico_id')->orWhere('medico_id', $medicoId));
    }

    /** Primer bloqueo activo que cubre la fecha para el médico, o null si la agenda está abierta. */
    public static function vigenteEn(int $medicoId, string $fecha): ?self
    {
        return static::query()->queCubren($medicoId, $fecha)->orderBy('id')->first();
    }

    public function getAlcanceAttribute(): string
    {
        return $this->medico_id ? ('Dr(a). ' . $this->medico?->apellidos) : 'Toda la clínica';
    }

    public function getPeriodoAttribute(): string
    {
        $i = $this->fecha_inicio->format('d/m/Y');
        $f = $this->fecha_fin->format('d/m/Y');

        return $i === $f ? $i : "{$i} al {$f}";
    }

    /** Citas programadas o confirmadas que caen dentro de este bloqueo (hay que reprogramarlas). */
    public function citasAfectadas()
    {
        return Cita::query()
            ->with(['paciente', 'medico'])
            ->whereIn('estado', [Cita::PROGRAMADA, Cita::CONFIRMADA])
            ->whereDate('fecha', '>=', $this->fecha_inicio->toDateString())
            ->whereDate('fecha', '<=', $this->fecha_fin->toDateString())
            ->when($this->medico_id, fn ($q) => $q->where('medico_id', $this->medico_id))
            ->orderBy('fecha')->orderBy('hora_inicio');
    }

    public function etiquetaBitacora(): string
    {
        return "{$this->motivo} · {$this->alcance} · {$this->periodo}";
    }
}
