<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

        // Cualquier falla inesperada al guardar/enviar (POST, PUT, PATCH, DELETE) vuelve a la pantalla
        // con un mensaje claro en lugar de una página 500 en blanco. Los GET usan errors/500.blade.php.
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->isMethodSafe()
                || config('app.debug')
                || $e instanceof HttpExceptionInterface
                || $e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof TokenMismatchException
                || $e instanceof ModelNotFoundException) {
                return null;
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'current_password']))
                ->with('error', 'No se pudo completar la operación por un problema del sistema. Inténtelo de nuevo; si persiste, avise al administrador.');
        });
    })->create();
