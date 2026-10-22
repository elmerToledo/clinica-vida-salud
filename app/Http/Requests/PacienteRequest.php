<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación en el servidor del paciente (CU08 y CU10, RF02).
 * Las mismas reglas básicas se aplican en el navegador con atributos HTML5.
 */
class PacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El acceso lo controla el middleware "rol" de las rutas.
    }

    public function rules(): array
    {
        $paciente = $this->route('paciente');
        $nombre = ['required', 'string', 'max:100', "regex:/^[\\pL\\s.'-]+$/u"];

        return [
            // RF02: no puede haber dos pacientes con la misma CI (al modificar se excluye al propio paciente).
            'ci' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z-]+$/', Rule::unique('pacientes', 'ci')->ignore($paciente?->id)],
            'nombres' => $nombre,
            'apellidos' => $nombre,
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'sexo' => ['required', Rule::in(['M', 'F'])],
            'telefono' => ['required', 'string', 'regex:/^[0-9+\s-]{7,20}$/'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'ci.unique' => 'Ya existe un paciente registrado con esa CI.',
            'ci.regex' => 'La CI solo puede tener letras, números y guiones.',
            'nombres.regex' => 'Los nombres solo pueden tener letras, espacios, puntos, apóstrofes y guiones.',
            'apellidos.regex' => 'Los apellidos solo pueden tener letras, espacios, puntos, apóstrofes y guiones.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'telefono.regex' => 'El teléfono debe tener entre 7 y 20 caracteres (números, espacios, + o guiones).',
        ];
    }
}
