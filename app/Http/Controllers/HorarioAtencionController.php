<?php

namespace App\Http\Controllers;

use App\Http\Requests\HorarioAtencionRequest;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Services\HorarioService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Horarios de atención de los médicos (CU20 a CU23).
 * Se desactivan, no se eliminan, para conservar el historial.
 */
class HorarioAtencionController extends Controller
{
    public function __construct(private HorarioService $horarios)
    {
    }

    /** CU21: lista los horarios por médico. Un médico solo ve los suyos. */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $soloPropios = $usuario->tieneRol('medico');
        $medicoId = $soloPropios ? ($usuario->medico?->id ?? 0) : $request->query('medico');
        $dia = $request->query('dia');

        $horarios = HorarioAtencion::query()
            ->with('medico')
            ->when($medicoId !== null && $medicoId !== '', fn ($q) => $q->where('medico_id', $medicoId))
            ->when($dia, fn ($q) => $q->where('dia_semana', (int) $dia))
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get()
            ->groupBy('medico_id')
            ->sortBy(fn ($grupo) => mb_strtolower($grupo->first()->medico->apellidos . ' ' . $grupo->first()->medico->nombres));

        return view('horarios.index', [
            'grupos' => $horarios,
            'medicos' => $soloPropios ? collect() : Medico::orderBy('apellidos')->orderBy('nombres')->get(),
            'medicoId' => $medicoId,
            'dia' => $dia,
            'soloPropios' => $soloPropios,
            'servicio' => $this->horarios,
        ]);
    }

    /** CU20 */
    public function create(Request $request): View
    {
        $horario = new HorarioAtencion([
            'duracion_cita_min' => 30,
            'activo' => true,
            'medico_id' => $request->query('medico'),
        ]);

        return view('horarios.form', $this->datosFormulario($horario));
    }

    /** CU20: la validación (traslapes, horas, duración) está en HorarioAtencionRequest. */
    public function store(HorarioAtencionRequest $request): RedirectResponse
    {
        try {
            HorarioAtencion::create($request->validated() + ['activo' => true]);
        } catch (QueryException $e) {
            // Respaldo: PostgreSQL también impide traslapes si dos personas guardan al mismo tiempo.
            return $this->errorDeTraslape($e);
        }

        return redirect()->route('horarios.index', ['medico' => $request->validated('medico_id')])
            ->with('exito', 'Horario de atención registrado.');
    }

    /** CU22 */
    public function edit(HorarioAtencion $horario): View
    {
        return view('horarios.form', $this->datosFormulario($horario));
    }

    /**
     * CU22: modifica el horario.
     * Pendiente del Ciclo 2: rechazar el cambio si hay citas programadas que quedarían fuera del horario.
     */
    public function update(HorarioAtencionRequest $request, HorarioAtencion $horario): RedirectResponse
    {
        try {
            $horario->update($request->validated());
        } catch (QueryException $e) {
            return $this->errorDeTraslape($e);
        }

        return redirect()->route('horarios.index', ['medico' => $horario->medico_id])
            ->with('exito', 'Horario de atención actualizado.');
    }

    /**
     * CU23: desactiva el horario (o lo reactiva si no se traslapa con otro activo).
     * Pendiente del Ciclo 2: rechazar la desactivación si hay citas futuras programadas en ese horario.
     */
    public function cambiarEstado(HorarioAtencion $horario): RedirectResponse
    {
        if (! $horario->activo) {
            $conflicto = $this->horarios->solapamiento(
                $horario->medico_id,
                $horario->dia_semana,
                substr($horario->hora_inicio, 0, 5),
                substr($horario->hora_fin, 0, 5),
                $horario->id
            );

            if ($conflicto) {
                return back()->with('error', 'No se puede reactivar: se traslapa con ' . $this->horarios->descripcion($conflicto) . '.');
            }
        }

        $horario->update(['activo' => ! $horario->activo]);

        return back()->with('exito', $horario->activo ? 'Horario reactivado.' : 'Horario desactivado.');
    }

    private function datosFormulario(HorarioAtencion $horario): array
    {
        // Solo médicos activos pueden recibir horarios (RN04); al editar se conserva el médico actual.
        $medicos = Medico::query()
            ->where('activo', true)
            ->when($horario->medico_id, fn ($q) => $q->orWhere('id', $horario->medico_id))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        return compact('horario', 'medicos');
    }

    private function errorDeTraslape(QueryException $e): RedirectResponse
    {
        if (str_contains($e->getMessage(), 'horarios_sin_solape')) {
            return back()->withInput()->withErrors(['hora_inicio' => 'Se traslapa con otro horario activo del médico.']);
        }

        throw $e;
    }
}
