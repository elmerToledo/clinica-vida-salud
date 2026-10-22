<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inicio</h2>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Bienvenida -->
            <div class="bg-white shadow sm:rounded-lg p-6 border-l-4 border-teal-600">
                <h3 class="text-lg font-semibold text-gray-900">Bienvenido(a), {{ $usuario->name }}</h3>
                <p class="mt-1 text-sm text-gray-600">Ingresó como <strong>{{ $usuario->rol_nombre }}</strong> a la Clínica Santa Aurora.</p>
            </div>

            <!-- Resumen -->
            @if (count($resumen) > 0)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($resumen as $etiqueta => $total)
                        <div class="bg-white shadow sm:rounded-lg p-5">
                            <div class="text-3xl font-semibold text-teal-700">{{ $total }}</div>
                            <div class="mt-1 text-sm text-gray-600">{{ $etiqueta }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Accesos según el rol -->
            @if (count($accesos) > 0)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($accesos as $acceso)
                        <a href="{{ route($acceso['ruta']) }}" class="block bg-white shadow sm:rounded-lg p-5 hover:ring-2 hover:ring-teal-500 transition">
                            <div class="font-semibold text-gray-900">{{ $acceso['etiqueta'] }}</div>
                            <div class="mt-1 text-sm text-gray-600">{{ $acceso['descripcion'] }}</div>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($usuario->tieneRol('paciente'))
                <div class="bg-white shadow sm:rounded-lg p-6 text-sm text-gray-700">
                    Aquí podrá reservar y consultar sus citas médicas. Este módulo se habilita en la siguiente etapa del sistema.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
