<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Médico de la clínica (RF03).
 *
 * Un médico inactivo no recibe nuevas citas (RN04), pero sus citas históricas
 * se conservan (RN11).
 */
class Medico extends Model
{
    use \App\Models\Concerns\RegistraBitacora;

    public const MODULO_BITACORA = 'medicos';

    protected $table = 'medicos';

    protected $fillable = [
        'user_id',
        'registro_profesional',
        'nombres',
        'apellidos',
        'telefono',
        'email',
        'puede_marcar_atendida',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            // Permiso para marcar citas como atendidas (CU30).
            'puede_marcar_atendida' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Código único legible (MED-0001), generado a partir del id al crear el registro.
        static::created(function (Medico $medico) {
            if (! $medico->codigo) {
                $medico->forceFill([
                    'codigo' => 'MED-' . str_pad((string) $medico->id, 4, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** Cuenta de usuario del médico (para consultar su propia agenda). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Especialidades que atiende (puede ser más de una). */
    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidad::class, 'especialidad_medico', 'medico_id', 'especialidad_id')
            ->withTimestamps();
    }

    /** Horarios de atención registrados. */
    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioAtencion::class, 'medico_id');
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

    /** Búsqueda por nombre, apellido o especialidad (RF03). */
    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return $query;
        }

        $like = '%' . mb_strtolower($texto) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->whereRaw('LOWER(nombres) LIKE ?', [$like])
                ->orWhereRaw('LOWER(apellidos) LIKE ?', [$like])
                ->orWhereHas('especialidades', function (Builder $e) use ($like) {
                    $e->whereRaw('LOWER(nombre) LIKE ?', [$like]);
                });
        });
    }

    /** Texto que se anota en la bitácora para identificar este registro. */
    public function etiquetaBitacora(): string
    {
        return "{$this->apellidos}, {$this->nombres} · Reg. {$this->registro_profesional}";
    }
}
