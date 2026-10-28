<?php

namespace App\Http\Controllers;

use App\Http\Requests\PacienteRequest;
use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pacientes (CU08 a CU11). Nunca se elimina un paciente: se desactiva (RN11).
 */
class PacienteController extends Controller
{
    /** CU09: listado con búsqueda por CI, nombres o apellidos y filtro por estado. */
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'todos');

        $pacientes = Paciente::query()
            ->buscar($request->query('q'))
            ->when($estado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($estado === 'inactivos', fn ($q) => $q->where('activo', false))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10)
            ->withQueryString();

        // Ficha rápida: el paciente elegido con ?ver=ID (por defecto, el primero de la página).
        $seleccionado = $pacientes->firstWhere('id', (int) $request->query('ver')) ?? $pacientes->first();
        $seleccionado?->load(['citas' => fn ($q) => $q->with(['medico', 'especialidad'])
            ->orderByDesc('fecha')->orderByDesc('hora_inicio')->limit(5)]);

        $resumen = [
            'total' => Paciente::count(),
            'activos' => Paciente::where('activo', true)->count(),
            'inactivos' => Paciente::where('activo', false)->count(),
            'conCitaHoy' => \App\Models\Cita::where('fecha', app(\App\Services\CitaService::class)->hoy())
                ->where('estado', '!=', 'cancelada')->distinct('paciente_id')->count('paciente_id'),
        ];

        return view('pacientes.index', compact('pacientes', 'estado', 'seleccionado', 'resumen'));
    }

    /** CU08: formulario de registro. */
    public function create(): View
    {
        return view('pacientes.form', ['paciente' => new Paciente(['activo' => true])]);
    }

    /** CU08: guarda el paciente como activo; el código PAC-0001 se genera en el modelo. */
    public function store(PacienteRequest $request): RedirectResponse
    {
        Paciente::create($request->validated() + ['activo' => true]);

        return redirect()->route('pacientes.index')->with('exito', 'Paciente registrado correctamente.');
    }

    /** CU10: formulario de modificación. */
    public function edit(Paciente $paciente): View
    {
        return view('pacientes.form', compact('paciente'));
    }

    /** CU10: actualiza los datos (la validación excluye la CI del propio paciente). */
    public function update(PacienteRequest $request, Paciente $paciente): RedirectResponse
    {
        $paciente->update($request->validated());

        return redirect()->route('pacientes.index')->with('exito', 'Datos del paciente actualizados.');
    }

    /** CU11: activa o desactiva sin eliminar el registro (conserva el historial de citas). */
    public function cambiarEstado(Paciente $paciente): RedirectResponse
    {
        $paciente->update(['activo' => ! $paciente->activo]);

        return back()->with('exito', $paciente->activo ? 'Paciente activado.' : 'Paciente desactivado.');
    }
}
