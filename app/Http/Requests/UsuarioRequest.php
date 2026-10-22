<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validación en el servidor de los usuarios (CU03 y CU05, RF01).
 * La contraseña es obligatoria al crear y opcional al modificar.
 */
class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Usuario y correo se guardan en minúsculas y sin espacios en los extremos. */
    protected function prepareForValidation(): void
    {
        $limpiar = [];
        foreach (['usuario', 'email'] as $campo) {
            if ($this->has($campo) && is_string($this->input($campo))) {
                $limpiar[$campo] = Str::lower(trim($this->input($campo)));
            }
        }
        $this->merge($limpiar);
    }

    public function rules(): array
    {
        $propioId = $this->route('usuario')?->id;
        $clave = [Password::min(8)->letters()->numbers(), 'confirmed'];

        return [
            'name' => ['required', 'string', 'max:100'],
            'usuario' => ['required', 'string', 'regex:/^[a-z0-9._-]{3,50}$/', Rule::unique('users', 'usuario')->ignore($propioId)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($propioId)],
            'rol' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => $propioId ? array_merge(['nullable'], $clave) : array_merge(['required'], $clave),
            'activo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.regex' => 'El usuario debe tener de 3 a 50 caracteres: letras minúsculas, números, punto, guion o guion bajo.',
            'usuario.unique' => 'Ese nombre de usuario ya está en uso.',
            'email.unique' => 'Ese correo ya está registrado.',
            'rol.in' => 'Seleccione un rol válido.',
        ];
    }
}
