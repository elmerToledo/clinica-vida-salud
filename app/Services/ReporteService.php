<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de los reportes (CU37 a CU42, RF13). Solo leen datos: no modifican nada.
 */
class ReporteService
{
    /** CU37: citas programadas y confirmadas (vigentes) entre dos fechas. */
    public function citasPorFecha(string $desde, string $hasta): Collection
    {
        return Cita::query()
            ->with(['paciente', 'medico', 'especialidad'])
            ->where('fecha', '>=', $desde)
            ->where('fecha', '<=', $hasta)
            ->whereIn('estado', [Cita::PROGRAMADA, Cita::CONFIRMADA])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();
    }

    /** CU38: total de citas de cada médico en el período, con su desglose por estado. */
    public function citasPorMedico(string $desde, string $hasta): Collection
    {
        return $this->agrupado('medicos', 'medico_id', ['t.nombres', 't.apellidos'], ['t.apellidos', 't.nombres'], $desde, $hasta);
    }

    /** CU39: total de citas de cada especialidad en el período, con su desglose por estado. */
    public function citasPorEspecialidad(string $desde, string $hasta): Collection
    {
        return $this->agrupado('especialidades', 'especialidad_id', ['t.nombre'], ['t.nombre'], $desde, $hasta);
    }

    /**
     * CU40: cuántas citas hay en cada estado dentro del período (incluye los estados sin citas).
     *
     * @return Collection<int, array{estado: string, nombre: string, total: int}>
     */
    public function citasPorEstado(string $desde, string $hasta): Collection
    {
        $conteos = Cita::query()
            ->where('fecha', '>=', $desde)
            ->where('fecha', '<=', $hasta)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return collect(Cita::ESTADOS)
            ->map(fn (string $nombre, string $estado) => ['estado' => $estado, 'nombre' => $nombre, 'total' => (int) ($conteos[$estado] ?? 0)])
            ->sortByDesc('total')
            ->values();
    }

    /** CU41: pacientes registrados (por fecha de alta) con el filtro de estado. */
    public function pacientes(?string $desde, ?string $hasta, string $estado = 'todos'): Builder
    {
        return Paciente::query()
            ->when($desde, fn ($q) => $q->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('created_at', '<=', $hasta))
            ->when($estado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($estado === 'inactivos', fn ($q) => $q->where('activo', false));
    }

    /**
     * CU42: atendidas, canceladas e inasistencias del período.
     *
     * @return array{atendidas: int, canceladas: int, inasistencias: int, total: int}
     */
    public function atendidasCanceladasInasistencias(string $desde, string $hasta): array
    {
        $porEstado = $this->citasPorEstado($desde, $hasta)->pluck('total', 'estado');

        $atendidas = (int) $porEstado[Cita::ATENDIDA];
        $canceladas = (int) $porEstado[Cita::CANCELADA];
        $inasistencias = (int) $porEstado[Cita::INASISTENCIA];

        return [
            'atendidas' => $atendidas,
            'canceladas' => $canceladas,
            'inasistencias' => $inasistencias,
            'total' => $atendidas + $canceladas + $inasistencias,
        ];
    }

    /**
     * Agrupa las citas de un período por médico o por especialidad; los que no tienen citas salen con 0.
     *
     * @param  array<int, string>  $columnas  columnas de la tabla agrupadora que se muestran
     * @param  array<int, string>  $orden  columnas para desempatar el orden
     */
    private function agrupado(string $tabla, string $clave, array $columnas, array $orden, string $desde, string $hasta): Collection
    {
        $consulta = DB::table("{$tabla} as t")
            ->leftJoin('citas as c', function ($join) use ($clave, $desde, $hasta) {
                $join->on("c.{$clave}", '=', 't.id')
                    ->where('c.fecha', '>=', $desde)
                    ->where('c.fecha', '<=', $hasta);
            })
            ->select(array_merge(['t.id'], $columnas))
            ->selectRaw('count(c.id) as total');

        foreach (array_keys(Cita::ESTADOS) as $estado) {
            $consulta->selectRaw("coalesce(sum(case when c.estado = ? then 1 else 0 end), 0) as {$estado}", [$estado]);
        }

        $consulta->groupBy(array_merge(['t.id'], $columnas))->orderByDesc('total');
        foreach ($orden as $columna) {
            $consulta->orderBy($columna);
        }

        return $consulta->get();
    }
}
