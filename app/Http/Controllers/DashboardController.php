<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use App\Support\Menu;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de inicio: resumen y accesos según el rol del usuario.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $usuario = $request->user();
        $resumen = [];

        if ($usuario->tieneRol('administrador')) {
            $resumen['Usuarios activos'] = User::where('activo', true)->count();
        }

        if ($usuario->tieneRol('administrador', 'recepcionista')) {
            $resumen['Pacientes activos'] = Paciente::activos()->count();
            $resumen['Médicos activos'] = Medico::activos()->count();
            $resumen['Especialidades activas'] = Especialidad::activas()->count();
        }

        if ($usuario->tieneRol('medico')) {
            $resumen['Mis horarios activos'] = $usuario->medico
                ? $usuario->medico->horarios()->activos()->count()
                : 0;
        }

        if ($usuario->tieneRol('paciente')) {
            $resumen['Mis próximas citas'] = $usuario->paciente
                ? $usuario->paciente->citas()
                    ->whereIn('estado', ['programada', 'confirmada'])
                    ->where('fecha', '>=', app(\App\Services\CitaService::class)->hoy())
                    ->count()
                : 0;
        }

        // El primer elemento del menú es "Inicio"; en las tarjetas solo van los módulos.
        $accesos = array_values(array_filter(Menu::para($usuario), fn (array $item) => $item['ruta'] !== 'dashboard'));

        return view('dashboard', compact('usuario', 'resumen', 'accesos'));
    }
}
