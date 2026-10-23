<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Services\CitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU36: el paciente consulta solo sus propias citas (y su historial, CU32).
 */
class MisCitasController extends Controller
{
    public function index(Request $request, CitaService $citas): View|RedirectResponse
    {
        $paciente = $request->user()->paciente;

        if (! $paciente) {
            return redirect()->route('perfil.edit')->with('error', 'Complete su perfil para ver y reservar citas.');
        }

        $hoy = $citas->hoy();

        $propias = Cita::query()
            ->with(['medico', 'especialidad'])
            ->where('paciente_id', $paciente->id)
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        // Próximas: vigentes de hoy en adelante. El resto es el historial, de la más reciente a la más antigua.
        [$proximas, $historial] = $propias->partition(
            fn (Cita $c) => $c->vigente && $c->fecha->toDateString() >= $hoy
        );

        return view('citas.mis-citas', [
            'proximas' => $proximas->values(),
            'historial' => $historial->sortByDesc(fn (Cita $c) => $c->fecha->toDateString() . $c->inicio)->values(),
            'paciente' => $paciente,
        ]);
    }
}
