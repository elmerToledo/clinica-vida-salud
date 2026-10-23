<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Consulta de la bitácora del sistema. Solo el Administrador accede. */
class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'modulo', 'accion', 'desde', 'hasta']);

        $registros = Bitacora::query()
            ->filtrar($filtros)
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('bitacora.index', compact('registros', 'filtros'));
    }
}
