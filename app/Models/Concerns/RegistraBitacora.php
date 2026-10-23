<?php

namespace App\Models\Concerns;

use App\Models\Bitacora;

/**
 * Anota en la bitácora cuando se crea o modifica un registro.
 * El modelo debe definir MODULO_BITACORA y etiquetaBitacora().
 */
trait RegistraBitacora
{
    /** Campos que cambian solos y no son una acción del usuario. */
    private static array $ignoradosBitacora = ['updated_at', 'remember_token', 'codigo', 'nombre_normalizado'];

    private static array $nombresCampos = ['password' => 'contraseña', 'rol' => 'rol', 'activo' => 'estado'];

    public static function bootRegistraBitacora(): void
    {
        static::created(function ($modelo) {
            Bitacora::registrar('crear', static::MODULO_BITACORA, $modelo->getKey(), $modelo->etiquetaBitacora());
        });

        static::updated(function ($modelo) {
            $cambios = array_values(array_diff(array_keys($modelo->getChanges()), self::$ignoradosBitacora));

            if ($cambios === []) {
                return;
            }

            if ($cambios === ['activo']) {
                Bitacora::registrar($modelo->activo ? 'activar' : 'desactivar', static::MODULO_BITACORA, $modelo->getKey(), $modelo->etiquetaBitacora());

                return;
            }

            $campos = array_map(fn ($c) => self::$nombresCampos[$c] ?? $c, $cambios);
            Bitacora::registrar('modificar', static::MODULO_BITACORA, $modelo->getKey(), $modelo->etiquetaBitacora() . ' · Campos: ' . implode(', ', $campos));
        });
    }
}
