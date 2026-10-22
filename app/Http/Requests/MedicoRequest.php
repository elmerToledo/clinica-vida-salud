<?php

namespace App\Http\Requests;

use App\Models\Especialidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación en el servidor del médico (CU12 y CU14, RF03, RN02).
 */
class MedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $medico = $this->route('medico');
        $nombre = ['required', 'string', 'max:100', "regex:/^[\\pL\\s.'-]+$/u"];

        // Solo se asocian especialidades registradas y activas. Al modificar se conservan
        // también las que el médico ya tenía, aunque hoy estén inactivas.
        $permitidas = Especialidad::activas()->pluck('id');
        if ($medico) {
            $permitidas = $permitidas->merge($medico->especialidades()->pluck('especialidades.id'));
        }

        return [
            // RN02: el número de registro profesional identifica al médico y no se repite.
            'registro_profesional' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\/. -]+$/', Rule::unique('medicos', 'registro_profesional')->ignore($medico?->id)],
            'nombres' => $nombre,
            'apellidos' => $nombre,
            'telefono' => ['nullable', 'string', 'regex:/^[0-9+\s-]{7,20}$/'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
            'especialidades' => ['required', 'array', 'min:1'],
            'especialidades.*' => ['integer', Rule::in($permitidas->unique()->values()->all())],
            // Cuenta de acceso opcional: debe ser de rol médico, estar activa y no estar usada por otro médico.
            'user_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('rol', 'medico')->where('activo', true),
                Rule::unique('medicos', 'user_id')->ignore($medico?->id),
            ],
            'puede_marcar_atendida' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'registro_profesional.unique' => 'Ya existe un médico con ese número de registro profesional.',
            'registro_profesional.regex' => 'El registro profesional solo puede tener letras, números, puntos, barras, espacios y guiones.',
            'nombres.regex' => 'Los nombres solo pueden tener letras, espacios, puntos, apóstrofes y guiones.',
            'apellidos.regex' => 'Los apellidos solo pueden tener letras, espacios, puntos, apóstrofes y guiones.',
            'especialidades.required' => 'Seleccione al menos una especialidad.',
            'especialidades.min' => 'Seleccione al menos una especialidad.',
            'especialidades.*.in' => 'Una de las especialidades seleccionadas no está disponible.',
            'user_id.exists' => 'La cuenta elegida debe ser de rol Médico y estar activa.',
            'user_id.unique' => 'Esa cuenta ya está asociada a otro médico.',
            'telefono.regex' => 'El teléfono debe tener entre 7 y 20 caracteres (números, espacios, + o guiones).',
        ];
    }
}
