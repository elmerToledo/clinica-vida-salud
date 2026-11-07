<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Historial médico (CU44): las atenciones ya realizadas.
 * El paciente ve solo las suyas; el médico ve únicamente a los pacientes que atendió él.
 */
class HistorialMedicoController extends Controller
{
    /** Paciente: su propio historial médico. */
    public function mio(Request $request): View|RedirectResponse
    {
        $paciente = $request->user()->paciente;

        if (! $paciente) {
            return redirect()->route('perfil.edit')->with('error', 'Complete su perfil para ver su historial médico.');
        }

        return view('historial.mio', ['paciente' => $paciente, 'atenciones' => $this->atenciones($paciente->id)]);
    }

    /** Médico: lista de pacientes que ha atendido. */
    public function pacientes(Request $request): View
    {
        $medicoId = $request->user()->medico?->id ?? 0;

        $pacientes = Paciente::query()
            ->whereIn('id', Cita::query()->where('medico_id', $medicoId)->where('estado', Cita::ATENDIDA)->select('paciente_id'))
            ->orderBy('apellidos')->orderBy('nombres')
            ->get();

        return view('historial.pacientes', ['pacientes' => $pacientes]);
    }

    /** Médico: historial de un paciente que atendió. */
    public function paciente(Request $request, Paciente $paciente): View
    {
        $medicoId = $request->user()->medico?->id ?? 0;

        $atendio = Cita::query()->where('medico_id', $medicoId)->where('paciente_id', $paciente->id)->where('estado', Cita::ATENDIDA)->exists();
        abort_unless($atendio, 403);

        return view('historial.paciente', ['paciente' => $paciente, 'atenciones' => $this->atenciones($paciente->id)]);
    }

    private function atenciones(int $pacienteId)
    {
        return Cita::query()
            ->with(['medico', 'especialidad'])
            ->where('paciente_id', $pacienteId)
            ->where('estado', Cita::ATENDIDA)
            ->orderByDesc('fecha')->orderByDesc('hora_inicio')
            ->get();
    }
}
