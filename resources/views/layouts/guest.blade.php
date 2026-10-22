<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Clínica Santa Aurora') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-tinta antialiased bg-fondo">
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            <!-- Panel de marca (solo pantallas grandes) -->
            <aside class="relative hidden overflow-hidden bg-deep p-12 text-crema lg:flex lg:flex-col lg:justify-between">
                <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-ambar/10 blur-3xl"></div>
                <a href="{{ url('/') }}" class="relative"><x-logo-aurora :light="true" /></a>
                <div class="relative">
                    <h2 class="font-display text-5xl font-semibold leading-tight">Su salud,<br><em class="text-ambar-claro">bien organizada.</em></h2>
                    <p class="mt-4 max-w-sm text-crema/80">Sistema de citas médicas de la Clínica Santa Aurora.</p>
                </div>
                <img src="{{ asset('img/c4.jpg') }}" alt="" class="relative h-48 w-full rounded-2xl object-cover opacity-90">
            </aside>

            <!-- Formulario -->
            <div class="flex min-h-screen flex-col items-center justify-center px-4 py-8 lg:min-h-0">
                <a href="{{ url('/') }}" class="mb-6 lg:hidden"><x-logo-aurora /></a>

                <div class="w-full max-w-md rounded-2xl border border-linea bg-white px-6 py-7 shadow-sm sm:px-8">
                    {{ $slot }}
                </div>

                <a href="{{ url('/') }}" class="mt-5 text-sm text-suave underline hover:text-tinta">‹ Volver al inicio</a>
            </div>
        </div>
    </body>
</html>
