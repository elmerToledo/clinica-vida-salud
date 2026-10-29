<?php

namespace App\Http\Controllers;

use App\Services\CitaService;
use App\Services\ReporteService;
use App\Support\Menu;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reportes administrativos (CU37 a CU42, RF13). Solo consultan: no modifican datos.
 * Los accesos por rol están en las rutas (CU37, CU38 y CU40 también para recepción).
 */
class ReporteController extends Controller
{
    public function __construct(private ReporteService $reportes, private CitaService $citas)
    {
    }

    public function index(Request $request): View
    {
        return view('reportes.index', ['reportes' => Menu::reportes($request->user())]);
    }

    /** CU37: por defecto, desde hoy hasta dentro de una semana. */
    public function porFecha(Request $request): View
    {
        $hoy = CarbonImmutable::createFromFormat('Y-m-d', $this->citas->hoy());
        [$desde, $hasta] = $this->rango($request, $hoy->toDateString(), $hoy->addDays(6)->toDateString());

        $citas = $this->reportes->citasPorFecha($desde, $hasta);

        return view('reportes.fecha', [
            'desde' => $desde,
            'hasta' => $hasta,
            'citas' => $citas,
            'porDia' => $citas->groupBy(fn ($c) => $c->fecha->toDateString()),
        ]);
    }

    /** CU38. */
    public function porMedico(Request $request): View
    {
        [$desde, $hasta] = $this->rango($request, ...$this->mesActual());

        return view('reportes.medico', ['desde' => $desde, 'hasta' => $hasta, 'filas' => $this->reportes->citasPorMedico($desde, $hasta)]);
    }

    /** CU39 (solo administrador). */
    public function porEspecialidad(Request $request): View
    {
        [$desde, $hasta] = $this->rango($request, ...$this->mesActual());

        return view('reportes.especialidad', ['desde' => $desde, 'hasta' => $hasta, 'filas' => $this->reportes->citasPorEspecialidad($desde, $hasta)]);
    }

    /** CU40. */
    public function porEstado(Request $request): View
    {
        [$desde, $hasta] = $this->rango($request, ...$this->mesActual());

        $filas = $this->reportes->citasPorEstado($desde, $hasta);

        return view('reportes.estado', ['desde' => $desde, 'hasta' => $hasta, 'filas' => $filas, 'total' => $filas->sum('total')]);
    }

    /** CU41 (solo administrador): sin fechas muestra todos los pacientes registrados. */
    public function pacientes(Request $request): View
    {
        $datos = $request->validate($this->reglasFechas($request), $this->mensajes());
        $estado = in_array($request->query('estado'), ['activos', 'inactivos'], true) ? $request->query('estado') : 'todos';
        $desde = $datos['desde'] ?? null;
        $hasta = $datos['hasta'] ?? null;

        $resumen = [
            'total' => $this->reportes->pacientes($desde, $hasta)->count(),
            'activos' => $this->reportes->pacientes($desde, $hasta, 'activos')->count(),
            'inactivos' => $this->reportes->pacientes($desde, $hasta, 'inactivos')->count(),
        ];

        return view('reportes.pacientes', [
            'desde' => $desde,
            'hasta' => $hasta,
            'estado' => $estado,
            'resumen' => $resumen,
            'pacientes' => $this->reportes->pacientes($desde, $hasta, $estado)
                ->orderByDesc('created_at')->orderBy('apellidos')->orderBy('nombres')
                ->paginate(15)->withQueryString(),
        ]);
    }

    /** CU42 (solo administrador). */
    public function periodo(Request $request): View
    {
        [$desde, $hasta] = $this->rango($request, ...$this->mesActual());

        return view('reportes.periodo', ['desde' => $desde, 'hasta' => $hasta, 'datos' => $this->reportes->atendidasCanceladasInasistencias($desde, $hasta)]);
    }

    /** Primer y último día del mes actual (período por defecto). */
    private function mesActual(): array
    {
        $hoy = CarbonImmutable::createFromFormat('Y-m-d', $this->citas->hoy());

        return [$hoy->startOfMonth()->toDateString(), $hoy->endOfMonth()->toDateString()];
    }

    /** Lee y valida el rango de fechas; si falta alguno usa el valor por defecto. */
    private function rango(Request $request, string $desdePorDefecto, string $hastaPorDefecto): array
    {
        $datos = $request->validate($this->reglasFechas($request), $this->mensajes());

        return [$datos['desde'] ?? $desdePorDefecto, $datos['hasta'] ?? $hastaPorDefecto];
    }

    private function reglasFechas(Request $request): array
    {
        return [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('desde') ? 'after_or_equal:desde' : null]),
        ];
    }

    private function mensajes(): array
    {
        return [
            'desde.date_format' => 'La fecha inicial no es válida.',
            'hasta.date_format' => 'La fecha final no es válida.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ];
    }
}
