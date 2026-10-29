<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="tema">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Clínica Santa Aurora') }}</title>

        @include('layouts.tema-script')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-fondo text-tinta">
        <div class="min-h-screen lg:flex" x-data="panelLateral">
            @include('layouts.navigation')

            <div class="flex-1 min-w-0">
                @include('layouts.topbar')

                <!-- Banner con el título de la pantalla -->
                @isset($header)
                    <header class="cabecera max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 sm:pt-8">
                        <div class="hero-banner p-6 md:p-10">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Aviso de cita guardada (arriba a la derecha, se cierra solo) -->
                @if (session('exito') && session('cita_id'))
                    <div x-data="{ ver: true }" x-init="setTimeout(() => ver = false, 12000)" x-show="ver" x-transition.opacity
                         role="status" class="fixed right-4 top-4 z-50 w-[min(24rem,calc(100vw-2rem))] rounded-2xl border border-[rgb(var(--c-ok)/.45)] bg-tarjeta p-4 shadow-lg">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-[rgb(var(--c-ok))]"><x-icono nombre="check-circle" class="h-6 w-6" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-tinta">Cita guardada con éxito</p>
                                <p class="mt-0.5 text-sm text-suave">{{ session('exito') }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="{{ route('dashboard') }}" class="inline-flex h-9 items-center rounded-lg border border-linea px-3 text-sm font-semibold text-tinta hover:bg-tenue">Volver al inicio</a>
                                    <a href="{{ route('citas.show', session('cita_id')) }}" class="inline-flex h-9 items-center rounded-lg bg-primary px-3 text-sm font-semibold text-sobre-primary hover:opacity-90">Ver cita</a>
                                </div>
                            </div>
                            <button type="button" @click="ver = false" aria-label="Cerrar aviso" class="rounded-lg p-1 text-suave hover:bg-tenue"><x-icono nombre="x" class="h-4 w-4" /></button>
                        </div>
                    </div>
                @endif

                <!-- Mensajes de la operación anterior (éxito o error) -->
                @if ((session('exito') && ! session('cita_id')) || session('error'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 space-y-2">
                        @if (session('exito') && ! session('cita_id'))
                            <div role="status" class="rounded-2xl border border-green-200 bg-green-50 p-4 text-[15px] font-medium text-green-800">{{ session('exito') }}</div>
                        @endif
                        @if (session('error'))
                            <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-[15px] font-medium text-red-800">{{ session('error') }}</div>
                        @endif
                    </div>
                @endif

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
