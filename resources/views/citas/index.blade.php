<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>{!! $soloPropias ? 'Mi <em>agenda</em>' : 'Agenda de <em>citas</em>' !!}</h2>
            @if ($puedeRegistrar)
                <x-boton :href="route('citas.disponibilidad')">Nueva cita</x-boton>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-filtros>
                @unless ($soloPropias)
                    <div>
                        <x-input-label for="medico" value="Médico" />
                        <select id="medico" name="medico" class="mt-1 campo">
                            <option value="">Todos</option>
                            @foreach ($medicos as $m)
                                <option value="{{ $m->id }}" @selected((string) $medicoId === (string) $m->id)>{{ $m->apellidos }}, {{ $m->nombres }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless
                <div>
                    <x-input-label for="desde" value="Desde" />
                    <input id="desde" name="desde" type="date" value="{{ $desde }}" class="mt-1 campo">
                </div>
                <div>
                    <x-input-label for="hasta" value="Hasta" />
                    <input id="hasta" name="hasta" type="date" value="{{ $hasta }}" class="mt-1 campo">
                </div>
                <div>
                    <x-input-label for="estado" value="Estado" />
                    <select id="estado" name="estado" class="mt-1 campo">
                        <option value="">Todos</option>
                        @foreach (\App\Models\Cita::ESTADOS as $valor => $nombre)
                            <option value="{{ $valor }}" @selected($estado === $valor)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-boton>Filtrar</x-boton>
                    <x-boton tipo="secundario" :href="route('citas.index')">Limpiar</x-boton>
                </div>
            </x-filtros>

            <div class="card-soft overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-ambar-claro/40 text-left text-xs font-bold uppercase tracking-wider text-deep">
                            <tr>
                                <th class="px-4 py-3">Fecha y hora</th>
                                <th class="px-4 py-3">Código</th>
                                <th class="px-4 py-3">Paciente</th>
                                @unless ($soloPropias)
                                    <th class="px-4 py-3 hidden md:table-cell">Médico</th>
                                @endunless
                                <th class="px-4 py-3 hidden sm:table-cell">Especialidad</th>
                                <th class="px-4 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-linea">
                            @forelse ($citas as $cita)
                                <tr class="{{ $cita->estado === 'cancelada' ? 'opacity-60' : '' }}">
                                    <td class="px-4 py-3 whitespace-nowrap font-medium">{{ $cita->fecha->format('d/m/Y') }} · {{ $cita->inicio }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap"><a href="{{ route('citas.show', $cita) }}" class="accion">{{ $cita->codigo }}</a></td>
                                    <td class="px-4 py-3">{{ $cita->paciente->apellidos }}, {{ $cita->paciente->nombres }}</td>
                                    @unless ($soloPropias)
                                        <td class="px-4 py-3 hidden md:table-cell">{{ $cita->medico->apellidos }}, {{ $cita->medico->nombres }}</td>
                                    @endunless
                                    <td class="px-4 py-3 hidden sm:table-cell">{{ $cita->especialidad->nombre }}</td>
                                    <td class="px-4 py-3"><x-cita-estado :estado="$cita->estado" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay citas con esos filtros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $citas->links() }}</div>
        </div>
    </div>
</x-app-layout>
