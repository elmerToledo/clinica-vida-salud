<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Paciente de la clínica (RF02).
 *
 * Nunca se elimina un paciente con historial: se desactiva (activo = false).
 */
class Paciente extends Model
{
    use \App\Models\Concerns\RegistraBitacora;

    public const MODULO_BITACORA = 'pacientes';

    protected $table = 'pacientes';

    protected $fillable = [
        'user_id',
        'ci',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'email',
        'direccion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Código único legible (PAC-0001), generado a partir del id al crear el registro.
        static::created(function (Paciente $paciente) {
            if (! $paciente->codigo) {
                $paciente->forceFill([
                    'codigo' => 'PAC-' . str_pad((string) $paciente->id, 4, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** Cuenta del portal asociada al paciente (si tiene). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Citas del paciente. */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    /** Nombre completo: "Nombres Apellidos". */
    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim($this->nombres . ' ' . $this->apellidos));
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Búsqueda por CI, nombres o apellidos (RF02). El texto va como parámetro, nunca concatenado. */
    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return $query;
        }

        $like = '%' . mb_strtolower($texto) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->whereRaw('LOWER(COALESCE(ci, \'\')) LIKE ?', [$like])
                ->orWhereRaw('LOWER(nombres) LIKE ?', [$like])
                ->orWhereRaw('LOWER(apellidos) LIKE ?', [$like]);
        });
    }

    /** Texto que se anota en la bitácora para identificar este registro. */
    public function etiquetaBitacora(): string
    {
        return "{$this->apellidos}, {$this->nombres} · CI {$this->ci}";
    }
}
