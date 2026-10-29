<?php

namespace App\Http\Controllers;

use App\Http\Requests\CitaRequest;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
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

    /** Paso 1 del asistente (solo recepción): elegir al paciente. */
    public function elegirPaciente(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $pacientes = Paciente::activos()
            ->buscar($q)
            ->when($q === '', fn ($c) => $c->orderByDesc('id'), fn ($c) => $c->orderBy('apellidos')->orderBy('nombres'))
            ->limit(8)
            ->get();

        return view('citas.paciente', compact('pacientes', 'q'));
    }

    /** CU24 (paso 2): especialidad, médico, fecha y bloques libres. */
    public function disponibilidad(Request $request): View
    {
        $usuario = $request->user();

        $todos = Medico::activos()->with('especialidades')->orderBy('apellidos')->orderBy('nombres')->get();
        $medico = $todos->firstWhere('id', (int) $request->query('medico'));
        $fecha = $this->fechaValida($request->query('fecha')) ?? $this->citas->hoy();

        $especialidades = Especialidad::activas()
            ->whereHas('medicos', fn ($q) => $q->where('medicos.activo', true))
            ->orderBy('nombre')
            ->get();
        $especialidad = $especialidades->firstWhere('id', (int) $request->query('especialidad'));
        $medicos = $especialidad
            ? $todos->filter(fn ($m) => $m->especialidades->contains('id', $especialidad->id))->values()
            : $todos;

        $pacienteElegido = $usuario->tieneRol('recepcionista')
            ? Paciente::activos()->find((int) $request->query('paciente'))
            : null;

        return view('citas.disponibilidad', [
            'medicos' => $medicos,
            'medico' => $medico,
            'especialidades' => $especialidades,
            'especialidad' => $especialidad,
            'pacienteElegido' => $pacienteElegido,
            'fecha' => $fecha,
            'hoy' => $this->citas->hoy(),
            'bloques' => $medico ? $this->citas->bloques($medico, $fecha) : collect(),
            'puedeReservar' => $usuario->tieneRol('recepcionista', 'paciente'),
            'sinPerfil' => $usuario->tieneRol('paciente') && ! $usuario->paciente,
        ]);
    }

    /** CU25 y CU35 (paso 3): confirmación del bloque elegido en la disponibilidad. */
    public function create(Request $request): View|RedirectResponse
    {
        $usuario = $request->user();
        $esPaciente = $usuario->tieneRol('paciente');

        // El paciente reserva a su nombre: antes necesita su ficha de paciente.
        if ($esPaciente && ! $usuario->paciente) {
            return redirect()->route('perfil.edit')->with('error', 'Complete su perfil para reservar una cita.');
        }

        $medico = Medico::activos()->with(['especialidades' => fn ($q) => $q->where('especialidades.activo', true)])
            ->find((int) $request->query('medico'));
        $fecha = $this->fechaValida($request->query('fecha'));
        $hora = (string) $request->query('hora');
        $pacienteElegido = $esPaciente ? null : Paciente::activos()->find((int) $request->query('paciente'));

        if (! $medico || ! $fecha || ! preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return redirect()->route('citas.disponibilidad', array_filter(['paciente' => $pacienteElegido?->id]))
                ->with('error', 'Elija primero un médico, una fecha y un bloque libre.');
        }

        // El bloque pudo ocuparse mientras se miraba la pantalla.
        try {
            $bloque = $this->citas->bloqueDisponible($medico, $fecha, $hora);
        } catch (ValidationException $e) {
            return redirect()
                ->route('citas.disponibilidad', array_filter(['medico' => $medico->id, 'fecha' => $fecha, 'paciente' => $pacienteElegido?->id]))
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return view('citas.form', [
            'medico' => $medico,
            'fecha' => $fecha,
            'bloque' => $bloque,
            'pacientes' => ($esPaciente || $pacienteElegido) ? null : Paciente::activos()->orderBy('apellidos')->orderBy('nombres')->get(),
            'miPaciente' => $esPaciente ? $usuario->paciente : null,
            'pacienteElegido' => $pacienteElegido,
            'especialidadElegida' => (int) $request->query('especialidad'),
        ]);
    }

    /** CU25: la validación está en CitaRequest y la disponibilidad se revalida en CitaService (RN13). */
    public function store(CitaRequest $request): RedirectResponse
    {
        $cita = $this->citas->registrar($request->validated(), $request->user());
        $mensaje = "Cita {$cita->codigo} registrada para el {$cita->fecha->format('d/m/Y')} a las {$cita->inicio}.";

        if ($request->user()->tieneRol('paciente')) {
            return redirect()->route('mis-citas.index')->with('exito', $mensaje)->with('cita_id', $cita->id);
        }

        return redirect()
            ->route('citas.index', ['desde' => $cita->fecha->toDateString(), 'hasta' => $cita->fecha->toDateString()])
            ->with('exito', $mensaje)
            ->with('cita_id', $cita->id);
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

    /** CU32: historial completo de citas de un paciente (pasadas, canceladas y futuras). */
    public function historialPaciente(Paciente $paciente): View
    {
        return view('citas.historial', [
            'paciente' => $paciente,
            'citas' => Cita::query()
                ->with(['medico', 'especialidad'])
                ->where('paciente_id', $paciente->id)
                ->orderByDesc('fecha')
                ->orderByDesc('hora_inicio')
                ->paginate(15),
        ]);
    }

    /** Detalle de una cita con las acciones que corresponden al rol y al estado. */
    public function show(Request $request, Cita $cita): View
    {
        $usuario = $request->user();
        $this->autorizarVer($cita, $usuario);

        $cita->load(['paciente', 'medico', 'especialidad', 'creador']);
        $llego = $cita->fecha->toDateString() <= $this->citas->hoy();

        return view('citas.show', [
            'cita' => $cita,
            'puedeGestionar' => $usuario->tieneRol('recepcionista') && $cita->vigente,
            'puedeCancelarPropia' => $usuario->tieneRol('paciente') && $cita->vigente,
            'puedeAtender' => $usuario->tieneRol('medico') && $cita->vigente && $llego && (bool) $usuario->medico?->puede_marcar_atendida,
            'yaLlego' => $llego,
        ]);
    }

    /** CU26: elegir un nuevo bloque libre del mismo médico. */
    public function reprogramarForm(Request $request, Cita $cita): View|RedirectResponse
    {
        if (! $cita->vigente) {
            return redirect()->route('citas.show', $cita)->withErrors(['estado' => 'Solo se reprograman citas programadas o confirmadas.']);
        }

        $cita->load(['paciente', 'medico']);
        $fecha = $this->fechaValida($request->query('fecha')) ?? max($cita->fecha->toDateString(), $this->citas->hoy());

        return view('citas.reprogramar', [
            'cita' => $cita,
            'fecha' => $fecha,
            'hoy' => $this->citas->hoy(),
            'bloques' => $this->citas->bloques($cita->medico, $fecha, $cita->id),
        ]);
    }

    /** CU26 */
    public function reprogramar(Request $request, Cita $cita): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
        ]);

        try {
            $this->citas->reprogramar($cita, $datos['fecha'], $datos['hora_inicio']);
        } catch (ValidationException $e) {
            return redirect()->route('citas.reprogramar', ['cita' => $cita, 'fecha' => $datos['fecha']])
                ->withErrors($e->errors());
        }

        return redirect()->route('citas.show', $cita)->with('exito', 'Cita reprogramada para el ' . $cita->fecha->format('d/m/Y') . ' a las ' . $cita->inicio . '.');
    }

    /** CU27: la recepcionista cancela cualquier cita; el paciente solo las suyas. El motivo es obligatorio. */
    public function cancelar(Request $request, Cita $cita): RedirectResponse
    {
        $usuario = $request->user();
        if ($usuario->tieneRol('paciente') && $usuario->paciente?->id !== $cita->paciente_id) {
            abort(403);
        }

        $datos = $request->validate(['motivo_cancelacion' => ['required', 'string', 'min:3', 'max:300']], [
            'motivo_cancelacion.required' => 'Escriba el motivo de la cancelación.',
            'motivo_cancelacion.min' => 'El motivo de la cancelación es muy corto.',
        ]);

        return $this->accion($cita, fn () => $this->citas->cancelar($cita, $datos['motivo_cancelacion']), 'Cita cancelada. El horario quedó libre.');
    }

    /** CU28 */
    public function confirmar(Cita $cita): RedirectResponse
    {
        return $this->accion($cita, fn () => $this->citas->confirmar($cita), 'Cita confirmada.');
    }

    /** CU29: registra la asistencia o la inasistencia del paciente. */
    public function asistencia(Request $request, Cita $cita): RedirectResponse
    {
        $tipo = $request->validate(['tipo' => ['required', 'in:asistio,inasistencia']])['tipo'];

        return $tipo === 'asistio'
            ? $this->accion($cita, fn () => $this->citas->registrarAsistencia($cita), 'Asistencia registrada.')
            : $this->accion($cita, fn () => $this->citas->registrarInasistencia($cita), 'Inasistencia registrada.');
    }

    /** CU30: solo el médico dueño de la cita y con el permiso asignado. */
    public function atender(Request $request, Cita $cita): RedirectResponse
    {
        $medico = $request->user()->medico;
        if (! $medico || $medico->id !== $cita->medico_id || ! $medico->puede_marcar_atendida) {
            abort(403);
        }

        return $this->accion($cita, fn () => $this->citas->marcarAtendida($cita), 'Cita marcada como atendida.');
    }

    /** Ejecuta una acción de estado y vuelve al detalle con el mensaje o con el motivo del rechazo. */
    private function accion(Cita $cita, \Closure $accion, string $mensaje): RedirectResponse
    {
        try {
            $accion();
        } catch (ValidationException $e) {
            return redirect()->route('citas.show', $cita)->withErrors($e->errors());
        }

        return redirect()->route('citas.show', $cita)->with('exito', $mensaje);
    }

    /** Administrador y recepcionista ven todas; el médico y el paciente, solo las suyas. */
    private function autorizarVer(Cita $cita, User $usuario): void
    {
        $permitido = match ($usuario->rol) {
            'administrador', 'recepcionista' => true,
            'medico' => $usuario->medico?->id === $cita->medico_id,
            'paciente' => $usuario->paciente?->id === $cita->paciente_id,
            default => false,
        };

        abort_unless($permitido, 403);
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
