<?php

namespace App\Http\Requests;

use App\Models\Especialidad;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación en el servidor de la especialidad (CU16 y CU18, RF04).
 */
class EspecialidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $propiaId = $this->route('especialidad')?->id;

        return [
            'nombre' => [
                'required', 'string', 'max:100',
                // RF04: no se repite el nombre normalizado (sin tildes, mayúsculas ni espacios de más).
                // Al modificar se excluye la propia especialidad.
                function (string $atributo, mixed $valor, \Closure $fallo) use ($propiaId) {
                    $existe = Especialidad::query()
                        ->where('nombre_normalizado', Especialidad::normalizar((string) $valor))
                        ->when($propiaId, fn ($q) => $q->where('id', '!=', $propiaId))
                        ->exists();

                    if ($existe) {
                        $fallo('Ya existe una especialidad con ese nombre.');
                    }
                },
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }
}
