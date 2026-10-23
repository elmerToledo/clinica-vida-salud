<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Cuenta de acceso al sistema (RF01).
 *
 * El rol define qué puede hacer la persona (RN12). La contraseña se guarda
 * siempre con hash (cast "hashed") y nunca se muestra en pantalla.
 */
#[Fillable(['name', 'usuario', 'email', 'password', 'rol', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use \App\Models\Concerns\RegistraBitacora;

    public const MODULO_BITACORA = 'usuarios';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Roles del sistema: valor guardado => nombre para mostrar. */
    public const ROLES = [
        'administrador' => 'Administrador',
        'recepcionista' => 'Recepcionista',
        'medico' => 'Médico',
        'paciente' => 'Paciente',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /** ¿El usuario tiene alguno de estos roles? */
    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }

    /** Nombre del rol para mostrar ("Médico", "Recepcionista"...). */
    protected function rolNombre(): Attribute
    {
        return Attribute::get(fn () => self::ROLES[$this->rol] ?? (string) $this->rol);
    }

    /** Ficha de médico asociada a esta cuenta (si el rol es médico). */
    public function medico(): HasOne
    {
        return $this->hasOne(Medico::class, 'user_id');
    }

    /** Ficha de paciente asociada a esta cuenta (si el rol es paciente). */
    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class, 'user_id');
    }

    /** Búsqueda por nombre, usuario o correo. El texto va como parámetro, nunca concatenado. */
    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return $query;
        }

        $like = '%' . mb_strtolower($texto) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->whereRaw('LOWER(name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(COALESCE(usuario, \'\')) LIKE ?', [$like])
                ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
        });
    }

    /** Genera un nombre de usuario libre a partir de un texto base (ej. la parte local del correo). */
    public static function generarUsuario(string $base): string
    {
        $limpio = substr((string) preg_replace('/[^a-z0-9._-]/', '', mb_strtolower($base)), 0, 40);
        $limpio = $limpio !== '' ? $limpio : 'usuario';

        $nombre = $limpio;
        $n = 1;
        while (self::where('usuario', $nombre)->exists()) {
            $n++;
            $nombre = $limpio . $n;
        }

        return $nombre;
    }

    /** Texto que se anota en la bitácora para identificar este registro. */
    public function etiquetaBitacora(): string
    {
        return "{$this->name} ({$this->usuario}) · rol: {$this->rol}";
    }
}
