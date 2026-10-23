@php($esAdmin = auth()->user()->tieneRol('administrador'))

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>
                {!! $soloPropios ? 'Mis horarios de <em>atención</em>' : 'Horarios de <em>atención</em>' !!}
            </h2>
            @if ($esAdmin)
                <x-boton :href="route('horarios.create', ['medico' => $medicoId])">Nuevo horario</x-boton>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <!-- Filtros (el médico solo ve sus propios horarios) -->
            @unless ($soloPropios)
                <x-filtros>
                    <div>
                        <x-input-label for="medico" value="Médico" />
                        <select id="medico" name="medico" class="mt-1 campo">
                            <option value="">Todos</option>
                            @foreach ($medicos as $medico)
                                <option value="{{ $medico->id }}" @selected((string) $medicoId === (string) $medico->id)>{{ $medico->apellidos }}, {{ $medico->nombres }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="dia" value="Día" />
                        <select id="dia" name="dia" class="mt-1 campo">
                            <option value="">Todos</option>
                            @foreach (\App\Models\HorarioAtencion::DIAS as $numero => $nombre)
                                <option value="{{ $numero }}" @selected((string) $dia === (string) $numero)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                        <x-boton>Filtrar</x-boton>
                        <x-boton tipo="secundario" :href="route('horarios.index')">Limpiar</x-boton>
                    </div>
                </x-filtros>
            @endunless

            @forelse ($grupos as $lista)
                @php($medico = $lista->first()->medico)
                <div class="card-soft overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-5 py-4 bg-ambar-claro/40 border-b border-linea">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $medico->nombres }} {{ $medico->apellidos }}</div>
                            <div class="text-xs text-gray-500">{{ $medico->codigo }} · {{ $medico->activo ? 'Activo' : 'Inactivo' }}</div>
                        </div>
                        @if ($esAdmin && $medico->activo)
                            <a href="{{ route('horarios.create', ['medico' => $medico->id]) }}" class="accion">+ Agregar horario</a>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="text-left text-xs font-bold uppercase tracking-wider text-deep">
                                <tr>
                                    <th class="px-4 py-2">Día</th>
                                    <th class="px-4 py-2">Desde</th>
                                    <th class="px-4 py-2">Hasta</th>
                                    <th class="px-4 py-2 hidden sm:table-cell">Duración de cita</th>
                                    <th class="px-4 py-2 hidden md:table-cell">Citas por jornada</th>
                                    <th class="px-4 py-2">Estado</th>
                                    @if ($esAdmin)
                                        <th class="px-4 py-2 text-right">Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-linea">
                                @foreach ($lista as $horario)
                                    <tr class="{{ $horario->activo ? '' : 'opacity-60' }}">
                                        <td class="px-4 py-2 font-medium">{{ $horario->dia_nombre }}</td>
                                        <td class="px-4 py-2">{{ substr($horario->hora_inicio, 0, 5) }}</td>
                                        <td class="px-4 py-2">{{ substr($horario->hora_fin, 0, 5) }}</td>
                                        <td class="px-4 py-2 hidden sm:table-cell">{{ $horario->duracion_cita_min }} min</td>
                                        <td class="px-4 py-2 hidden md:table-cell">{{ intdiv($servicio->minutos(substr($horario->hora_inicio, 0, 5), substr($horario->hora_fin, 0, 5)), $horario->duracion_cita_min) }}</td>
                                        <td class="px-4 py-2"><x-estado-badge :activo="$horario->activo" /></td>
                                        @if ($esAdmin)
                                            <td class="px-4 py-2 text-right whitespace-nowrap space-x-1">
                                                <a href="{{ route('horarios.edit', $horario) }}" class="accion">Editar</a>
                                                <form method="POST" action="{{ route('horarios.estado', $horario) }}" class="inline"
                                                      onsubmit="return confirm('{{ $horario->activo ? '¿Desactivar este horario? El historial se conserva.' : '¿Reactivar este horario?' }}');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="accion {{ $horario->activo ? 'accion-peligro' : 'accion-ok' }}">
                                                        {{ $horario->activo ? 'Desactivar' : 'Reactivar' }}
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="card-soft p-8 text-center text-gray-500">
                    {{ $soloPropios ? 'Todavía no tiene horarios de atención asignados.' : 'No hay horarios registrados con esos filtros.' }}
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
