<?php

namespace App\Http\Controllers;

use App\Models\CorreoEnviado;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Constancia de correos enviados: página independiente (sin enlace en el menú) para demostrar
 * qué correos envió el sistema, aun cuando se simulan y no llegan a una bandeja real.
 */
class ConstanciaCorreosController extends Controller
{
    private const ZONA = 'America/La_Paz';

    public function index(Request $request): View
    {
        $datos = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'estado' => ['nullable', 'in:enviado,simulado,fallido'],
        ]);

        $consulta = CorreoEnviado::query()->latest('id');

        if (! empty($datos['desde'])) {
            $consulta->where('created_at', '>=', Carbon::parse($datos['desde'], self::ZONA)->startOfDay()->utc());
        }
        if (! empty($datos['hasta'])) {
            $consulta->where('created_at', '<=', Carbon::parse($datos['hasta'], self::ZONA)->endOfDay()->utc());
        }
        if (! empty($datos['estado'])) {
            $consulta->where('estado', $datos['estado']);
        }

        return view('constancia.correos', [
            'correos' => $consulta->limit(300)->get(),
            'modo' => CorreoEnviado::modoActual(),
            'simulado' => CorreoEnviado::modoSimulado(),
            'zona' => self::ZONA,
            'filtros' => $datos,
            'generado' => now(self::ZONA),
        ]);
    }
}
