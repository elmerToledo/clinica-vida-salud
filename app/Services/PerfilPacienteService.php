<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Cuenta y perfil del paciente (CU33 y CU34).
 *
 * Si recepción ya había registrado al paciente (misma CI y misma fecha de nacimiento) y esa ficha
 * no tiene cuenta, la cuenta nueva se vincula a ella: así conserva su historial de citas.
 */
class PerfilPacienteService
{
    /**
     * Motivo por el que la CI no se puede usar, o null si está libre o se puede vincular.
     * $propio es la ficha del usuario que edita su perfil (si ya tiene una).
     */
    public function conflictoCi(string $ci, ?string $fechaNacimiento, ?Paciente $propio = null): ?string
    {
        $otra = Paciente::query()
            ->where('ci', $ci)
            ->when($propio, fn ($q) => $q->where('id', '!=', $propio->id))
            ->first();

        if (! $otra) {
            return null;
        }

        if ($otra->user_id !== null) {
            return 'Esa CI ya pertenece a otra cuenta de paciente.';
        }

        // Ficha de recepción sin cuenta: solo se vincula quien tampoco tenga ficha y coincida la fecha de nacimiento.
        if (! $propio && $fechaNacimiento && $otra->fecha_nacimiento->toDateString() === $fechaNacimiento) {
            return null;
        }

        return 'Ya existe un paciente registrado con esa CI. Si usted ya se atiende en la clínica, consulte en recepción.';
    }

    /** CU33: crea la cuenta (siempre con rol paciente, RN12) y su ficha en una sola operación. */
    public function registrar(array $datos): User
    {
        return DB::transaction(function () use ($datos) {
            $usuario = User::create([
                'name' => trim($datos['nombres'] . ' ' . $datos['apellidos']),
                'usuario' => User::generarUsuario(strstr($datos['email'], '@', true) ?: $datos['email']),
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'rol' => 'paciente',
                'activo' => true,
            ]);

            $this->guardar($usuario, $datos);

            return $usuario;
        });
    }

    /** CU34: actualiza la ficha del usuario; si no la tenía, la crea o la vincula a la de recepción. */
    public function guardar(User $usuario, array $datos): Paciente
    {
        $campos = [
            'ci' => $datos['ci'],
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'sexo' => $datos['sexo'],
            'telefono' => $datos['telefono'],
            'email' => $datos['email'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
        ];

        $ficha = $usuario->paciente;

        if (! $ficha) {
            $ficha = Paciente::query()->where('ci', $datos['ci'])->whereNull('user_id')->first();
        }

        if ($ficha) {
            $ficha->fill($campos + ['user_id' => $usuario->id])->save();

            return $ficha;
        }

        return Paciente::create($campos + ['user_id' => $usuario->id, 'activo' => true]);
    }
}
