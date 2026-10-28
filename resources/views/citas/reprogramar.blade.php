<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>Reprogramar <em>{{ $cita->codigo }}</em></h2>
            <x-boton tipo="secundario" :href="route('citas.show', $cita)">Volver a la cita</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $mensaje)
                            <li>{{ $mensaje }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card-soft p-5 text-sm">
                <div class="font-semibold text-gray-900">{{ $cita->paciente->nombres }} {{ $cita->paciente->apellidos }} con {{ $cita->medico->nombres }} {{ $cita->medico->apellidos }}</div>
                <div class="text-gray-600">Cita actual: {{ $cita->fecha->format('d/m/Y') }} · {{ $cita->inicio }} a {{ $cita->fin }}</div>
            </div>

            <x-filtros>
                <div>
                    <x-input-label for="fecha" value="Nueva fecha" />
                    <input id="fecha" name="fecha" type="date" value="{{ $fecha }}" min="{{ $hoy }}" class="mt-1 campo" required>
                </div>
                <div class="flex gap-2">
                    <x-boton>Ver horarios</x-boton>
                </div>
            </x-filtros>

            <div class="card-soft overflow-hidden">
                <div class="px-5 py-4 bg-ambar-claro/40 border-b border-linea font-semibold text-gray-900">
                    Horarios del {{ \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $fecha)->format('d/m/Y') }}
                </div>
                @if ($bloques->isEmpty())
                    <div class="p-8 text-center text-gray-500">El médico no tiene horario de atención ese día.</div>
                @else
                    <div class="p-5 grid gap-3 grid-cols-2 sm:grid-cols-3 md:grid-cols-4">
                        @foreach ($bloques as $bloque)
                            @if ($bloque['estado'] === 'libre')
                                <form method="POST" action="{{ route('citas.reprogramar', $cita) }}"
                                      onsubmit="return confirm('¿Mover la cita al {{ \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $fecha)->format('d/m/Y') }} a las {{ $bloque['inicio'] }}?');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="fecha" value="{{ $fecha }}">
                                    <input type="hidden" name="hora_inicio" value="{{ $bloque['inicio'] }}">
                                    <button type="submit" class="w-full rounded-2xl border border-green-300 bg-green-50 px-4 py-3 text-center hover:bg-green-100 transition">
                                        <div class="font-semibold text-ok">{{ $bloque['inicio'] }} – {{ $bloque['fin'] }}</div>
                                        <div class="text-xs text-ok">Libre · Elegir</div>
                                    </button>
                                </form>
                            @else
                                <div class="rounded-2xl border border-gray-200 bg-gray-100 px-4 py-3 text-center text-gray-500">
                                    <div class="font-semibold">{{ $bloque['inicio'] }} – {{ $bloque['fin'] }}</div>
                                    <div class="text-xs">{{ $bloque['estado'] === 'ocupado' ? 'Ocupado' : 'Ya pasó' }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
