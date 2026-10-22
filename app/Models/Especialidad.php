<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Especialidad médica (RF04).
 *
 * No se permiten dos especialidades con el mismo nombre normalizado
 * (sin tildes, en minúsculas y sin espacios repetidos).
 */
class Especialidad extends Model
{
    // Laravel pluralizaría "Especialidad" en inglés; se indica el nombre real de la tabla.
    protected $table = 'especialidades';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /** "  Pediatría  " y "pediatria" producen el mismo valor: "pediatria". */
    public static function normalizar(string $nombre): string
    {
        $limpio = mb_strtolower(Str::ascii($nombre));

        return trim((string) preg_replace('/\s+/', ' ', $limpio));
    }

    protected static function booted(): void
    {
        // Antes de guardar se limpia el nombre y se calcula su versión normalizada.
        static::saving(function (Especialidad $especialidad) {
            $especialidad->nombre = trim((string) preg_replace('/\s+/u', ' ', (string) $especialidad->nombre));
            $especialidad->nombre_normalizado = self::normalizar($especialidad->nombre);
        });
    }

    /** Médicos que atienden esta especialidad. */
    public function medicos(): BelongsToMany
    {
        return $this->belongsToMany(Medico::class, 'especialidad_medico', 'especialidad_id', 'medico_id')
            ->withTimestamps();
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
