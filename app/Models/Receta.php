<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Receta médica de una cita: la escribe el médico, la publica para que el paciente la vea y se puede imprimir.
 */
class Receta extends Model
{
    public const MODULO_BITACORA = 'recetas';

    protected $table = 'recetas';

    protected $fillable = ['cita_id', 'medico_id', 'paciente_id', 'diagnostico', 'medicamentos', 'indicaciones', 'publicada', 'publicada_at'];

    protected function casts(): array
    {
        return ['publicada' => 'boolean', 'publicada_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Código único legible (REC-00001), generado a partir del id.
        static::created(function (Receta $receta) {
            if (! $receta->codigo) {
                $receta->forceFill(['codigo' => 'REC-' . str_pad((string) $receta->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
