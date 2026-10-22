@php($esAdmin = auth()->user()->tieneRol('administrador'))

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $soloPropios ? 'Mis horarios de atención' : 'Horarios de atención' }}
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
                        <select id="medico" name="medico" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Todos</option>
                            @foreach ($medicos as $medico)
                                <option value="{{ $medico->id }}" @selected((string) $medicoId === (string) $medico->id)>{{ $medico->apellidos }}, {{ $medico->nombres }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="dia" value="Día" />
                        <select id="dia" name="dia" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $medico->nombres }} {{ $medico->apellidos }}</div>
                            <div class="text-xs text-gray-500">{{ $medico->codigo }} · {{ $medico->activo ? 'Activo' : 'Inactivo' }}</div>
                        </div>
                        @if ($esAdmin && $medico->activo)
                            <a href="{{ route('horarios.create', ['medico' => $medico->id]) }}" class="text-sm font-medium text-teal-700 hover:text-teal-900">+ Agregar horario</a>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
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
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($lista as $horario)
                                    <tr class="{{ $horario->activo ? '' : 'bg-gray-50 text-gray-500' }}">
                                        <td class="px-4 py-2 font-medium">{{ $horario->dia_nombre }}</td>
                                        <td class="px-4 py-2">{{ substr($horario->hora_inicio, 0, 5) }}</td>
                                        <td class="px-4 py-2">{{ substr($horario->hora_fin, 0, 5) }}</td>
                                        <td class="px-4 py-2 hidden sm:table-cell">{{ $horario->duracion_cita_min }} min</td>
                                        <td class="px-4 py-2 hidden md:table-cell">{{ intdiv($servicio->minutos(substr($horario->hora_inicio, 0, 5), substr($horario->hora_fin, 0, 5)), $horario->duracion_cita_min) }}</td>
                                        <td class="px-4 py-2"><x-estado-badge :activo="$horario->activo" /></td>
                                        @if ($esAdmin)
                                            <td class="px-4 py-2 text-right whitespace-nowrap space-x-3">
                                                <a href="{{ route('horarios.edit', $horario) }}" class="font-medium text-teal-700 hover:text-teal-900">Editar</a>
                                                <form method="POST" action="{{ route('horarios.estado', $horario) }}" class="inline"
                                                      onsubmit="return confirm('{{ $horario->activo ? '¿Desactivar este horario? El historial se conserva.' : '¿Reactivar este horario?' }}');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="font-medium {{ $horario->activo ? 'text-red-600 hover:text-red-800' : 'text-green-700 hover:text-green-900' }}">
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
                <div class="bg-white shadow sm:rounded-lg p-8 text-center text-gray-500">
                    {{ $soloPropios ? 'Todavía no tiene horarios de atención asignados.' : 'No hay horarios registrados con esos filtros.' }}
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
