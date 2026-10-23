<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cita médica (RF06 a RF12).
 *
 * Nunca se elimina: se cancela y el horario queda libre (RN09, RN11).
 * Los cambios de estado se anotan en la bitácora desde CitaService.
 */
class Cita extends Model
{
    public const MODULO_BITACORA = 'citas';

    public const PROGRAMADA = 'programada';
    public const CONFIRMADA = 'confirmada';
    public const ATENDIDA = 'atendida';
    public const CANCELADA = 'cancelada';
    public const INASISTENCIA = 'inasistencia';

    /** Estado guardado => nombre para mostrar. */
    public const ESTADOS = [
        self::PROGRAMADA => 'Programada',
        self::CONFIRMADA => 'Confirmada',
        self::ATENDIDA => 'Atendida',
        self::CANCELADA => 'Cancelada',
        self::INASISTENCIA => 'Inasistencia',
    ];

    protected $table = 'citas';

    protected $fillable = [
        'paciente_id',
        'medico_id',
        'especialidad_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'motivo_consulta',
        'motivo_cancelacion',
        'llegada_at',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            // Con formato propio se guarda "2026-10-26", sin hora, igual en PostgreSQL y en SQLite.
            'fecha' => 'date:Y-m-d',
            'llegada_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Código único legible (CIT-00001), generado a partir del id al crear el registro.
        static::created(function (Cita $cita) {
            if (! $cita->codigo) {
                $cita->forceFill([
                    'codigo' => 'CIT-' . str_pad((string) $cita->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /** Nombre del estado para mostrar. */
    public function getEstadoNombreAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? (string) $this->estado;
    }

    /** Hora "HH:MM" sin segundos (PostgreSQL devuelve "HH:MM:SS"). */
    public function getInicioAttribute(): string
    {
        return substr((string) $this->hora_inicio, 0, 5);
    }

    public function getFinAttribute(): string
    {
        return substr((string) $this->hora_fin, 0, 5);
    }

    /** ¿Aún se puede cancelar, reprogramar o confirmar? (programada o confirmada) */
    public function getVigenteAttribute(): bool
    {
        return in_array($this->estado, [self::PROGRAMADA, self::CONFIRMADA], true);
    }

    /** Las citas canceladas no ocupan el horario (RN09). */
    public function scopeOcupan(Builder $query): Builder
    {
        return $query->where('estado', '!=', self::CANCELADA);
    }

    /** Texto que se anota en la bitácora para identificar este registro. */
    public function etiquetaBitacora(): string
    {
        $paciente = $this->paciente?->nombre_completo ?? ('Paciente #' . $this->paciente_id);
        $medico = $this->medico?->apellidos ?? ('Médico #' . $this->medico_id);

        return trim("{$this->codigo} · {$paciente} con {$medico} · {$this->fecha?->format('d/m/Y')} {$this->inicio}");
    }
}
