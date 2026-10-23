<?php

namespace App\Http\Controllers;

use App\Http\Requests\CitaRequest;
use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use App\Services\CitaService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Citas médicas: disponibilidad (CU24), registro (CU25) y agenda (CU31).
 */
class CitaController extends Controller
{
    public function __construct(private CitaService $citas)
    {
    }

    /** CU24: bloques libres y ocupados de un médico en una fecha. */
    public function disponibilidad(Request $request): View
    {
        $medicos = Medico::activos()->with('especialidades')->orderBy('apellidos')->orderBy('nombres')->get();
        $medico = $medicos->firstWhere('id', (int) $request->query('medico'));
        $fecha = $this->fechaValida($request->query('fecha')) ?? $this->citas->hoy();

        return view('citas.disponibilidad', [
            'medicos' => $medicos,
            'medico' => $medico,
            'fecha' => $fecha,
            'hoy' => $this->citas->hoy(),
            'bloques' => $medico ? $this->citas->bloques($medico, $fecha) : collect(),
            'puedeReservar' => $request->user()->tieneRol('recepcionista'),
        ]);
    }

    /** CU25: formulario para el bloque elegido en la disponibilidad. */
    public function create(Request $request): View|RedirectResponse
    {
        $medico = Medico::activos()->with(['especialidades' => fn ($q) => $q->where('especialidades.activo', true)])
            ->find((int) $request->query('medico'));
        $fecha = $this->fechaValida($request->query('fecha'));
        $hora = (string) $request->query('hora');

        if (! $medico || ! $fecha || ! preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return redirect()->route('citas.disponibilidad')->with('error', 'Elija primero un médico, una fecha y un bloque libre.');
        }

        // El bloque pudo ocuparse mientras se miraba la pantalla.
        try {
            $bloque = $this->citas->bloqueDisponible($medico, $fecha, $hora);
        } catch (ValidationException $e) {
            return redirect()
                ->route('citas.disponibilidad', ['medico' => $medico->id, 'fecha' => $fecha])
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return view('citas.form', [
            'medico' => $medico,
            'fecha' => $fecha,
            'bloque' => $bloque,
            'pacientes' => Paciente::activos()->orderBy('apellidos')->orderBy('nombres')->get(),
        ]);
    }

    /** CU25: la validación está en CitaRequest y la disponibilidad se revalida en CitaService (RN13). */
    public function store(CitaRequest $request): RedirectResponse
    {
        $cita = $this->citas->registrar($request->validated(), $request->user());

        return redirect()
            ->route('citas.index', ['desde' => $cita->fecha->toDateString(), 'hasta' => $cita->fecha->toDateString()])
            ->with('exito', "Cita {$cita->codigo} registrada para el {$cita->fecha->format('d/m/Y')} a las {$cita->inicio}.");
    }

    /** CU31: agenda por médico, fecha y estado. Un médico solo ve sus propias citas. */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $soloPropias = $usuario->tieneRol('medico');
        $medicoId = $soloPropias ? ($usuario->medico?->id ?? 0) : $request->query('medico');

        $hoy = $this->citas->hoy();
        $desde = $request->has('desde') ? $this->fechaValida($request->query('desde')) : $hoy;
        $hasta = $this->fechaValida($request->query('hasta'));
        $estado = array_key_exists((string) $request->query('estado'), Cita::ESTADOS) ? $request->query('estado') : null;

        $citas = Cita::query()
            ->with(['paciente', 'medico', 'especialidad'])
            ->when($medicoId !== null && $medicoId !== '', fn ($q) => $q->where('medico_id', $medicoId))
            ->when($desde, fn ($q) => $q->where('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->where('fecha', '<=', $hasta))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->paginate(20)
            ->withQueryString();

        return view('citas.index', [
            'citas' => $citas,
            'medicos' => $soloPropias ? collect() : Medico::orderBy('apellidos')->orderBy('nombres')->get(),
            'medicoId' => $medicoId,
            'desde' => $desde,
            'hasta' => $hasta,
            'estado' => $estado,
            'soloPropias' => $soloPropias,
            'puedeRegistrar' => $usuario->tieneRol('recepcionista'),
        ]);
    }

    /** Devuelve la fecha en formato Y-m-d si es válida; si no, null. */
    private function fechaValida(mixed $texto): ?string
    {
        $texto = (string) $texto;
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
            return null;
        }

        $fecha = CarbonImmutable::createFromFormat('Y-m-d', $texto);

        return $fecha && $fecha->format('Y-m-d') === $texto ? $texto : null;
    }
}
