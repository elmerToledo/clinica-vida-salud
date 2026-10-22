<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el acceso solo a los usuarios cuyo rol esté en la lista (RF01, RN12).
 *
 * Uso en rutas:
 *   Route::middleware(['auth', 'rol:administrador'])->group(...);
 *   Route::middleware(['auth', 'rol:administrador,recepcionista'])->group(...);
 */
class RolMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        // Sin sesión o con un rol no autorizado: acceso denegado.
        if (! $usuario || ! in_array($usuario->rol, $roles, true)) {
            abort(403, 'No tiene permiso para realizar esta operación.');
        }

        return $next($request);
    }
}
