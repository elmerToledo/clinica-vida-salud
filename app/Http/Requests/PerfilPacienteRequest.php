<?php

namespace App\Http\Requests;

use App\Services\PerfilPacienteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Validación en el servidor del registro de cuenta (CU33, sin sesión) y del perfil del paciente (CU34, con sesión).
 */
class PerfilPacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $registro = $this->user() === null;
        $nombre = ['required', 'string', 'max:100', "regex:/^[\\pL\\s.'-]+$/u"];

        $reglas = [
            'ci' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z-]+$/'],
            'nombres' => $nombre,
            'apellidos' => $nombre,
            'fecha_nacimiento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after:1900-01-01'],
            'sexo' => ['required', Rule::in(['M', 'F'])],
            'telefono' => ['required', 'string', 'regex:/^[0-9+\s-]{7,20}$/'],
            'direccion' => ['nullable', 'string', 'max:255'],
            // Al registrarse, el correo es también el de acceso; en el perfil es solo el de contacto.
            'email' => $registro
                ? ['required', 'string', 'lowercase', 'email', 'max:150', Rule::unique('users', 'email')]
                : ['nullable', 'string', 'email', 'max:150'],
        ];

        if ($registro) {
            $reglas['password'] = ['required', 'confirmed', Password::min(8)->letters()->numbers()];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'ci.regex' => 'La CI solo puede tener letras, números y guiones.',
            'nombres.regex' => 'Los nombres solo pueden tener letras, espacios, puntos, apóstrofes y guiones.',
            'apellidos.regex' => 'Los apellidos solo pueden tener letras, espacios, puntos, apóstrofes y guiones.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'telefono.regex' => 'El teléfono debe tener entre 7 y 20 caracteres (números, espacios, + o guiones).',
            'email.unique' => 'Ya existe una cuenta con ese correo.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $mensaje = app(PerfilPacienteService::class)->conflictoCi(
                    (string) $this->input('ci'),
                    (string) $this->input('fecha_nacimiento'),
                    $this->user()?->paciente
                );

                if ($mensaje) {
                    $validator->errors()->add('ci', $mensaje);
                }
            },
        ];
    }
}
