<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de un feriado o bloqueo de agenda (CU46).
 */
class BloqueoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Vacío = toda la clínica (feriado).
            'medico_id' => ['nullable', 'integer', Rule::exists('medicos', 'id')],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'motivo' => ['required', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_inicio.required' => 'Indique la fecha inicial del bloqueo.',
            'fecha_fin.required' => 'Indique la fecha final del bloqueo.',
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'motivo.required' => 'Escriba el motivo del bloqueo (por ejemplo: Feriado nacional).',
            'medico_id.exists' => 'Seleccione un médico de la lista.',
        ];
    }
}
