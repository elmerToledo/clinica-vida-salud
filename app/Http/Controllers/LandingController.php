<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/** Página pública de la clínica. Si hay una sesión abierta, la página lo avisa y deja elegir entre continuar o cerrar sesión. */
class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('landing');
    }
}
