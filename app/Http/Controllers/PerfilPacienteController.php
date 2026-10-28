<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerfilPacienteRequest;
use App\Models\Paciente;
use App\Services\PerfilPacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU34: cada paciente edita solo el perfil de su propia sesión.
 * Si su cuenta aún no tiene ficha (cuentas anteriores al registro con datos), aquí la completa.
 */
class PerfilPacienteController extends Controller
{
    public function edit(Request $request): View
    {
        $paciente = $request->user()->paciente ?? new Paciente(['activo' => true]);

        return view('perfil.edit', ['paciente' => $paciente, 'completando' => ! $paciente->exists]);
    }

    public function update(PerfilPacienteRequest $request, PerfilPacienteService $servicio): RedirectResponse
    {
        $nueva = ! $request->user()->paciente;
        $servicio->guardar($request->user(), $request->validated());

        return redirect()
            ->route($nueva ? 'citas.disponibilidad' : 'perfil.edit')
            ->with('exito', $nueva ? 'Perfil completado. Ya puede reservar su cita.' : 'Sus datos fueron actualizados.');
    }
}
