<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>Disponibilidad de <em>citas</em></h2>
            @if (auth()->user()->tieneRol('recepcionista'))
                <x-boton tipo="secundario" :href="route('citas.index')">Ver agenda</x-boton>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-filtros>
                <div>
                    <x-input-label for="medico" value="Médico" />
                    <select id="medico" name="medico" class="mt-1 campo" required>
                        <option value="">Seleccione…</option>
                        @foreach ($medicos as $m)
                            <option value="{{ $m->id }}" @selected($medico?->id === $m->id)>
                                {{ $m->apellidos }}, {{ $m->nombres }} — {{ $m->especialidades->pluck('nombre')->join(', ') ?: 'sin especialidad' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="fecha" value="Fecha" />
                    <input id="fecha" name="fecha" type="date" value="{{ $fecha }}" min="{{ $hoy }}" class="mt-1 campo" required>
                </div>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                    <x-boton>Consultar</x-boton>
                </div>
            </x-filtros>

            @if (! $medico)
                <div class="card-soft p-8 text-center text-gray-500">Elija un médico y una fecha para ver los horarios libres.</div>
            @else
                @php
                    $libres = $bloques->where('estado', 'libre')->count();
                    $diaNombre = \App\Models\HorarioAtencion::DIAS[\Carbon\CarbonImmutable::createFromFormat('Y-m-d', $fecha)->dayOfWeekIso];
                @endphp

                <div class="card-soft overflow-hidden">
                    <div class="px-5 py-4 bg-ambar-claro/40 border-b border-linea">
                        <div class="font-semibold text-gray-900">{{ $medico->nombres }} {{ $medico->apellidos }}</div>
                        <div class="text-sm text-gray-600">
                            {{ $diaNombre }} {{ \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $fecha)->format('d/m/Y') }}
                            · {{ $libres }} {{ $libres === 1 ? 'bloque libre' : 'bloques libres' }}
                        </div>
                    </div>

                    @if ($bloques->isEmpty())
                        <div class="p-8 text-center text-gray-500">El médico no tiene horario de atención ese día.</div>
                    @else
                        <div class="p-5 grid gap-3 grid-cols-2 sm:grid-cols-3 md:grid-cols-4">
                            @foreach ($bloques as $bloque)
                                @php($ruta = route('citas.create', ['medico' => $medico->id, 'fecha' => $fecha, 'hora' => $bloque['inicio']]))
                                @if ($bloque['estado'] === 'libre' && $puedeReservar)
                                    <a href="{{ $ruta }}" class="rounded-2xl border border-green-300 bg-green-50 px-4 py-3 text-center hover:bg-green-100 transition">
                                        <div class="font-semibold text-ok">{{ $bloque['inicio'] }} – {{ $bloque['fin'] }}</div>
                                        <div class="text-xs text-ok">Libre · Reservar</div>
                                    </a>
                                @elseif ($bloque['estado'] === 'libre')
                                    <div class="rounded-2xl border border-green-300 bg-green-50 px-4 py-3 text-center">
                                        <div class="font-semibold text-ok">{{ $bloque['inicio'] }} – {{ $bloque['fin'] }}</div>
                                        <div class="text-xs text-ok">Libre</div>
                                    </div>
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
            @endif
        </div>
    </div>
</x-app-layout>
