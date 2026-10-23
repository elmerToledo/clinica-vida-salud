<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['rol' => \App\Http\Middleware\RolMiddleware::class]);
        $middleware->web(append: [\App\Http\Middleware\UsuarioActivo::class]);
        // Detrás del balanceador de la nube (HTTPS): confiar en los encabezados X-Forwarded-*.
        $middleware->trustProxies(at: '*');
        // Quien no tiene sesión y entra a una pantalla interna ve primero la página pública de la clínica.
        $middleware->redirectGuestsTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
