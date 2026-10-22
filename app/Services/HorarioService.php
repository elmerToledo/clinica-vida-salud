<?php

namespace App\Services;

use App\Models\HorarioAtencion;

/**
 * Reglas de los horarios de atención (RF05, RN05, RN08).
 *
 * Las horas se manejan como texto "HH:MM"; al ser del mismo formato,
 * compararlas como texto equivale a compararlas como horas.
 */
class HorarioService
{
    /**
     * Busca un horario ACTIVO del mismo médico y día que se traslape con el intervalo dado.
     * Dos intervalos se traslapan si cada uno empieza antes de que termine el otro.
     * Devuelve el horario en conflicto o null si no hay ninguno.
     */
    public function solapamiento(int $medicoId, int $dia, string $inicio, string $fin, ?int $ignorarId = null): ?HorarioAtencion
    {
        return HorarioAtencion::query()
            ->where('medico_id', $medicoId)
            ->where('dia_semana', $dia)
            ->where('activo', true)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->get()
            ->first(fn (HorarioAtencion $h) => $inicio < substr($h->hora_fin, 0, 5) && $fin > substr($h->hora_inicio, 0, 5));
    }

    /** Minutos entre dos horas "HH:MM". */
    public function minutos(string $inicio, string $fin): int
    {
        [$h1, $m1] = array_map('intval', explode(':', $inicio));
        [$h2, $m2] = array_map('intval', explode(':', $fin));

        return ($h2 * 60 + $m2) - ($h1 * 60 + $m1);
    }

    /** Texto legible del intervalo: "Lunes 08:00 - 12:00". */
    public function descripcion(HorarioAtencion $horario): string
    {
        return sprintf(
            '%s %s - %s',
            HorarioAtencion::DIAS[$horario->dia_semana] ?? '',
            substr($horario->hora_inicio, 0, 5),
            substr($horario->hora_fin, 0, 5)
        );
    }
}
