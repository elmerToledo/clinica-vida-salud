<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use App\Models\Medico;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Médicos y horarios de atención a simple vista, para que el paciente sepa a quién buscar.
 * Solo consulta: no modifica datos.
 */
class DirectorioController extends Controller
{
    public function index(Request $request): View
    {
        $especialidades = Especialidad::activas()
            ->whereHas('medicos', fn ($q) => $q->where('medicos.activo', true))
            ->orderBy('nombre')
            ->get();
        $especialidad = $especialidades->firstWhere('id', (int) $request->query('especialidad'));

        $medicos = Medico::activos()
            ->with([
                'especialidades' => fn ($q) => $q->where('especialidades.activo', true),
                'horarios' => fn ($q) => $q->where('activo', true)->orderBy('dia_semana')->orderBy('hora_inicio'),
            ])
            ->when($especialidad, fn ($q) => $q->whereHas('especialidades', fn ($e) => $e->where('especialidades.id', $especialidad->id)))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        return view('directorio.index', [
            'medicos' => $medicos,
            'especialidades' => $especialidades,
            'especialidad' => $especialidad,
            'puedeReservar' => $request->user()->tieneRol('recepcionista', 'paciente'),
        ]);
    }
}
