<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de los usuarios desactivados por el Administrador (CU06).
 *
 * Un usuario inactivo no puede usar el sistema: si lo desactivan con la sesión
 * abierta, se cierra su sesión en la siguiente petición.
 */
class UsuarioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->activo === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Su cuenta está desactivada. Comuníquese con el administrador.',
            ]);
        }

        return $next($request);
    }
}
