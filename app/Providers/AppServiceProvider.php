<?php

namespace App\Providers;

use App\Models\Bitacora;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mensajes y fechas en español (archivos en lang/es).
        $this->app->setLocale('es');
        Carbon::setLocale('es');

        // Bitácora: inicios y cierres de sesión, e intentos fallidos (nunca se guarda la contraseña).
        Event::listen(Login::class, fn (Login $e) => Bitacora::registrar('ingreso', 'sesion', $e->user->getAuthIdentifier(), 'Inicio de sesión', $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user
            ? Bitacora::registrar('salida', 'sesion', $e->user->getAuthIdentifier(), 'Cierre de sesión', $e->user)
            : null);
        Event::listen(Failed::class, fn (Failed $e) => Bitacora::registrar(
            'ingreso_fallido', 'sesion', null, 'Intento de ingreso fallido', $e->user,
            (string) ($e->credentials['usuario'] ?? $e->credentials['email'] ?? ''),
        ));
    }
}
