<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Horario de atención de un médico (RF05).
 *
 * dia_semana: 1 = lunes ... 7 = domingo (estándar ISO).
 * hora_inicio y hora_fin llegan como texto "HH:MM:SS".
 */
class HorarioAtencion extends Model
{
    use \App\Models\Concerns\RegistraBitacora;

    public const MODULO_BITACORA = 'horarios';

    // Laravel pluralizaría el nombre en inglés; se indica el nombre real de la tabla.
    protected $table = 'horarios_atencion';

    /** Nombres de los días para mostrar en pantalla. */
    public const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    protected $fillable = [
        'medico_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'duracion_cita_min',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'duracion_cita_min' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    /** Nombre del día ("Lunes", "Martes"...). */
    protected function diaNombre(): Attribute
    {
        return Attribute::get(fn () => self::DIAS[$this->dia_semana] ?? '');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Texto que se anota en la bitácora para identificar este registro. */
    public function etiquetaBitacora(): string
    {
        return trim((($this->medico?->apellidos) ?? ('Médico #' . $this->medico_id)) . ' · ' . $this->dia_nombre . ' ' . substr((string) $this->hora_inicio, 0, 5) . '-' . substr((string) $this->hora_fin, 0, 5));
    }
}
