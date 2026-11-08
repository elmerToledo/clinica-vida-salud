<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Receta médica: el médico la escribe, la publica para el paciente y la puede imprimir.
 * Una vez publicada ya no se modifica.
 */
class RecetaService
{
    public function __construct(private readonly CitaService $citas)
    {
    }

    /** ¿Puede este médico recetar en esta cita? Es su cita, ya llegó la fecha y no está cancelada ni sin asistencia. */
    public function puedeRecetar(Cita $cita, User $usuario): bool
    {
        return $usuario->tieneRol('medico')
            && $usuario->medico?->id === $cita->medico_id
            && in_array($cita->estado, [Cita::PROGRAMADA, Cita::CONFIRMADA, Cita::ATENDIDA], true)
            && $cita->fecha->toDateString() <= $this->citas->hoy();
    }

    /**
     * Crea o actualiza el borrador de la receta.
     *
     * @param array{diagnostico: string, medicamentos: string, indicaciones?: ?string} $datos
     */
    public function guardar(Cita $cita, array $datos, User $medico): Receta
    {
        $receta = $cita->receta;

        if ($receta?->publicada) {
            throw ValidationException::withMessages(['diagnostico' => 'La receta ya fue publicada y no se puede modificar.']);
        }

        $campos = [
            'diagnostico' => trim($datos['diagnostico']),
            'medicamentos' => trim($datos['medicamentos']),
            'indicaciones' => isset($datos['indicaciones']) && trim($datos['indicaciones']) !== '' ? trim($datos['indicaciones']) : null,
        ];

        if ($receta) {
            $receta->update($campos);
            $accion = 'modificar';
        } else {
            $receta = Receta::create($campos + [
                'cita_id' => $cita->id,
                'medico_id' => $cita->medico_id,
                'paciente_id' => $cita->paciente_id,
            ]);
            $accion = 'crear';
        }

        Bitacora::registrar($accion, Receta::MODULO_BITACORA, $receta->id, "{$receta->codigo} · {$cita->codigo}");

        return $receta;
    }

    /** Publica la receta: desde ese momento el paciente la ve en su cita y en su historial. */
    public function publicar(Cita $cita): Receta
    {
        $receta = $cita->receta;

        if (! $receta) {
            throw ValidationException::withMessages(['diagnostico' => 'Primero escriba y guarde la receta.']);
        }

        if (! $receta->publicada) {
            $receta->update(['publicada' => true, 'publicada_at' => now()]);
            Bitacora::registrar('publicar', Receta::MODULO_BITACORA, $receta->id, "{$receta->codigo} · {$cita->codigo}");
        }

        return $receta;
    }
}
