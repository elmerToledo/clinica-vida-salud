<?php

namespace App\Http\Requests;

use App\Services\HorarioService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validación en el servidor del horario de atención (CU20 y CU22, RF05, RN05, RN08).
 */
class HorarioAtencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // RN04: solo un médico activo recibe nuevos horarios.
            'medico_id' => ['required', 'integer', Rule::exists('medicos', 'id')->where('activo', true)],
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            // RN08: la hora final debe ser posterior a la inicial.
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'duracion_cita_min' => ['required', 'integer', 'between:5,240'],
        ];
    }

    public function messages(): array
    {
        return [
            'medico_id.exists' => 'Seleccione un médico activo.',
            'hora_fin.after' => 'La hora final debe ser posterior a la hora inicial.',
            'duracion_cita_min.between' => 'La duración de la cita debe estar entre 5 y 240 minutos.',
        ];
    }

    /** Reglas que necesitan consultar la base de datos; solo se ejecutan si lo anterior es válido. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $servicio = app(HorarioService::class);
                $inicio = (string) $this->input('hora_inicio');
                $fin = (string) $this->input('hora_fin');

                // RN08: el horario debe permitir al menos una cita completa.
                if ($servicio->minutos($inicio, $fin) < (int) $this->input('duracion_cita_min')) {
                    $validator->errors()->add('duracion_cita_min', 'La duración de la cita no cabe en el horario indicado.');

                    return;
                }

                // RN05: un médico no puede tener dos horarios traslapados el mismo día.
                $conflicto = $servicio->solapamiento(
                    (int) $this->input('medico_id'),
                    (int) $this->input('dia_semana'),
                    $inicio,
                    $fin,
                    $this->route('horario')?->id
                );

                if ($conflicto) {
                    $validator->errors()->add('hora_inicio', 'Se traslapa con otro horario activo del médico: ' . $servicio->descripcion($conflicto) . '.');
                }
            },
        ];
    }
}
