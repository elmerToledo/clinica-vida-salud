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
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-fondo text-tinta">
        <div class="min-h-screen lg:flex">
            @include('layouts.navigation')

            <div class="flex-1 min-w-0">
                <!-- Page Heading -->
                @isset($header)
                    <header class="cabecera border-b border-linea bg-white/60">
                        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Mensajes de la operación anterior (éxito o error) -->
                @if (session('exito') || session('error'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                        @if (session('exito'))
                            <div role="status" class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('exito') }}</div>
                        @endif
                        @if (session('error'))
                            <div role="alert" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
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
