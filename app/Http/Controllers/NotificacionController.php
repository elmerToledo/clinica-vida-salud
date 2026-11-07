<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Campanita del paciente (CU43): lista sus avisos y los marca como leídos. Cada usuario ve solo los suyos.
 */
class NotificacionController extends Controller
{
    public function index(Request $request): View
    {
        return view('notificaciones.index', [
            'notificaciones' => $request->user()->notifications()->paginate(15),
        ]);
    }

    public function leer(Request $request, string $id): RedirectResponse
    {
        $n = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $n->markAsRead();

        return redirect()->route('notificaciones.index');
    }

    public function leerTodas(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('exito', 'Todos los avisos quedaron marcados como leídos.');
    }
}
