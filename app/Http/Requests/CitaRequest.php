<?php

namespace App\Http\Requests;

use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validación en el servidor al registrar una cita (CU25, RF06, RF07, RN03 a RN08).
 * La disponibilidad del bloque se revalida dentro de la transacción de CitaService.
 */
class CitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'medico_id' => ['required', 'integer', 'exists:medicos,id'],
            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'motivo_consulta' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'paciente_id.required' => 'Seleccione un paciente.',
            'medico_id.required' => 'Seleccione un médico.',
            'especialidad_id.required' => 'Seleccione la especialidad de la consulta.',
            'fecha.date_format' => 'Indique una fecha válida.',
            'hora_inicio.date_format' => 'Indique una hora válida.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $paciente = Paciente::find($this->input('paciente_id'));
                if (! $paciente?->activo) {
                    $validator->errors()->add('paciente_id', 'El paciente está inactivo y no puede recibir citas.');
                }

                $medico = Medico::find($this->input('medico_id'));
                if (! $medico?->activo) {
                    $validator->errors()->add('medico_id', 'El médico está inactivo y no recibe citas nuevas.');

                    return;
                }

                $atiende = $medico->especialidades()
                    ->where('especialidades.id', $this->input('especialidad_id'))
                    ->where('especialidades.activo', true)
                    ->exists();

                if (! $atiende) {
                    $validator->errors()->add('especialidad_id', 'El médico no atiende esa especialidad.');
                }
            },
        ];
    }
}
