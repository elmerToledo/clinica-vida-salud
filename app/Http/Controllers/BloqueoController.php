<?php

namespace App\Http\Controllers;

use App\Http\Requests\BloqueoRequest;
use App\Models\Bitacora;
use App\Models\Bloqueo;
use App\Models\Medico;
use App\Services\CitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Feriados y bloqueos de agenda (CU46). Solo el administrador. Se desactivan, no se eliminan.
 */
class BloqueoController extends Controller
{
    public function __construct(private CitaService $citas)
    {
    }

    public function index(): View
    {
        $hoy = $this->citas->hoy();

        $bloqueos = Bloqueo::query()
            ->with('medico')
            ->orderByDesc('activo')
            ->orderByRaw('case when fecha_fin >= ? then 0 else 1 end', [$hoy])
            ->orderBy('fecha_inicio')
            ->get();

        return view('bloqueos.index', ['bloqueos' => $bloqueos, 'hoy' => $hoy]);
    }

    public function create(): View
    {
        return view('bloqueos.form', ['medicos' => Medico::where('activo', true)->orderBy('apellidos')->orderBy('nombres')->get()]);
    }

    public function store(BloqueoRequest $request): RedirectResponse
    {
        $bloqueo = Bloqueo::create($request->validated() + ['activo' => true, 'creado_por' => $request->user()->id]);
        Bitacora::registrar('crear', Bloqueo::MODULO_BITACORA, $bloqueo->id, $bloqueo->etiquetaBitacora());

        $afectadas = $bloqueo->citasAfectadas()->count();
        $mensaje = 'Bloqueo registrado: no se podrán reservar citas en ese período.';

        if ($afectadas > 0) {
            // Las citas ya agendadas no se cancelan solas: recepción debe reprogramarlas con el paciente.
            return redirect()->route('bloqueos.index')->with('exito', $mensaje)
                ->with('error', "Atención: hay {$afectadas} " . ($afectadas === 1 ? 'cita ya agendada' : 'citas ya agendadas')
                    . ' en ese período. Pida a recepción que las reprograme o las cancele.');
        }

        return redirect()->route('bloqueos.index')->with('exito', $mensaje);
    }

    public function cambiarEstado(Bloqueo $bloqueo): RedirectResponse
    {
        $bloqueo->update(['activo' => ! $bloqueo->activo]);
        Bitacora::registrar($bloqueo->activo ? 'activar' : 'desactivar', Bloqueo::MODULO_BITACORA, $bloqueo->id, $bloqueo->etiquetaBitacora());

        return back()->with('exito', $bloqueo->activo ? 'Bloqueo reactivado.' : 'Bloqueo desactivado: la agenda vuelve a estar abierta.');
    }
}
