<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Pacientes</h2>
                <p>Registro, búsqueda y ficha rápida de cada paciente.</p>
            </div>
            <x-boton :href="route('pacientes.create')">Nuevo paciente</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            <!-- Resumen -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                @foreach ([['Total', $resumen['total']], ['Activos', $resumen['activos']], ['Inactivos', $resumen['inactivos']], ['Con cita hoy', $resumen['conCitaHoy']]] as [$etiqueta, $valor])
                    <div class="card-soft p-4 sm:p-5">
                        <p class="text-sm text-suave">{{ $etiqueta }}</p>
                        <p class="mt-1 text-3xl font-bold text-tinta"><x-contador :valor="$valor" /></p>
                    </div>
                @endforeach
            </div>

            <!-- CU09: búsqueda -->
            <x-filtros>
                <div class="sm:col-span-2">
                    <x-input-label for="q" value="Buscar" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="request('q')" placeholder="CI, nombres o apellidos" maxlength="100" />
                </div>
                <div>
                    <x-input-label for="estado" value="Estado" />
                    <select id="estado" name="estado" class="mt-1 campo">
                        <option value="todos" @selected($estado === 'todos')>Todos</option>
                        <option value="activos" @selected($estado === 'activos')>Activos</option>
                        <option value="inactivos" @selected($estado === 'inactivos')>Inactivos</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-boton>Buscar</x-boton>
                    <x-boton tipo="secundario" :href="route('pacientes.index')">Limpiar</x-boton>
                </div>
            </x-filtros>

            <div class="grid gap-5 lg:grid-cols-3 items-start">
                <!-- Lista -->
                <div class="lg:col-span-2 space-y-4">
                    <x-tabla>
                        <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
                            <tr>
                                <th class="px-5 py-4">Paciente</th>
                                <th class="px-5 py-4 hidden md:table-cell">CI</th>
                                <th class="px-5 py-4 hidden sm:table-cell">Teléfono</th>
                                <th class="px-5 py-4">Estado</th>
                                <th class="px-5 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-linea">
                            @forelse ($pacientes as $paciente)
                                @php
                                    $esElegido = $seleccionado && $seleccionado->id === $paciente->id;
                                    $iniciales = mb_strtoupper(mb_substr($paciente->nombres, 0, 1).mb_substr($paciente->apellidos, 0, 1));
                                @endphp
                                <tr class="{{ $paciente->activo ? '' : 'opacity-60' }} {{ $esElegido ? 'bg-tenue' : '' }}">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('pacientes.index', array_merge(request()->query(), ['ver' => $paciente->id])) }}" class="flex items-center gap-3">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-primary/15 text-sm font-bold text-primary">{{ $iniciales }}</span>
                                            <span class="min-w-0">
                                                <span class="block font-semibold text-tinta">{{ $paciente->apellidos }}, {{ $paciente->nombres }}</span>
                                                <span class="block text-sm text-suave">{{ $paciente->codigo }} · {{ $paciente->fecha_nacimiento->age }} años</span>
                                            </span>
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 hidden md:table-cell whitespace-nowrap">{{ $paciente->ci }}</td>
                                    <td class="px-5 py-3 hidden sm:table-cell whitespace-nowrap">{{ $paciente->telefono }}</td>
                                    <td class="px-5 py-3"><x-estado-badge :activo="$paciente->activo" /></td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap space-x-1">
                                        <a href="{{ route('pacientes.historial', $paciente) }}" class="accion">Citas</a>
                                        <a href="{{ route('pacientes.edit', $paciente) }}" class="accion">Editar</a>
                                        @if (auth()->user()->tieneRol('administrador'))
                                            <form method="POST" action="{{ route('pacientes.estado', $paciente) }}" class="inline"
                                                  onsubmit="return confirm('{{ $paciente->activo ? '¿Desactivar este paciente? Su historial se conserva.' : '¿Activar este paciente?' }}');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="accion {{ $paciente->activo ? 'accion-peligro' : 'accion-ok' }}">
                                                    {{ $paciente->activo ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-suave">No se encontraron pacientes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-tabla>

                    <div>{{ $pacientes->links() }}</div>
                </div>

                <!-- Ficha rápida -->
                <aside class="card-soft p-5 lg:sticky lg:top-6" aria-label="Ficha rápida del paciente">
                    @if ($seleccionado)
                        <div class="flex items-center gap-3">
                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-primary/15 text-lg font-bold text-primary">{{ mb_strtoupper(mb_substr($seleccionado->nombres, 0, 1).mb_substr($seleccionado->apellidos, 0, 1)) }}</span>
                            <div class="min-w-0">
                                <p class="text-lg font-bold text-tinta">{{ $seleccionado->nombre_completo }}</p>
                                <p class="text-sm text-suave">{{ $seleccionado->codigo }}</p>
                            </div>
                        </div>
                        <div class="mt-3"><x-estado-badge :activo="$seleccionado->activo" /></div>

                        <dl class="mt-4 grid grid-cols-2 gap-x-3 gap-y-3 text-sm">
                            <div><dt class="text-suave">CI</dt><dd class="font-semibold text-tinta">{{ $seleccionado->ci }}</dd></div>
                            <div><dt class="text-suave">Edad</dt><dd class="font-semibold text-tinta">{{ $seleccionado->fecha_nacimiento->age }} años</dd></div>
                            <div><dt class="text-suave">Teléfono</dt><dd class="font-semibold text-tinta">{{ $seleccionado->telefono ?: '—' }}</dd></div>
                            <div><dt class="text-suave">Nacimiento</dt><dd class="font-semibold text-tinta">{{ $seleccionado->fecha_nacimiento->format('d/m/Y') }}</dd></div>
                            @if ($seleccionado->email)
                                <div class="col-span-2"><dt class="text-suave">Correo</dt><dd class="font-semibold text-tinta break-all">{{ $seleccionado->email }}</dd></div>
                            @endif
                        </dl>

                        <h3 class="mt-5 text-sm font-bold uppercase tracking-wider text-suave">Últimas citas</h3>
                        <ul class="mt-2 divide-y divide-linea">
                            @forelse ($seleccionado->citas as $cita)
                                <li class="py-2.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <a href="{{ route('citas.show', $cita) }}" class="text-sm font-semibold text-tinta hover:underline">{{ $cita->fecha->format('d/m/Y') }} · {{ $cita->inicio }}</a>
                                        <x-cita-estado :estado="$cita->estado" />
                                    </div>
                                    <p class="text-sm text-suave">{{ $cita->medico?->nombre_completo }} · {{ $cita->especialidad?->nombre }}</p>
                                </li>
                            @empty
                                <li class="py-3 text-sm text-suave">Aún no tiene citas registradas.</li>
                            @endforelse
                        </ul>

                        <div class="mt-5 flex flex-wrap gap-2">
                            @if (auth()->user()->tieneRol('recepcionista') && $seleccionado->activo)
                                <x-boton :href="route('citas.disponibilidad', ['paciente' => $seleccionado->id])">Agendar cita</x-boton>
                            @endif
                            <x-boton tipo="secundario" :href="route('pacientes.historial', $seleccionado)">Historial</x-boton>
                            <x-boton tipo="secundario" :href="route('pacientes.edit', $seleccionado)">Editar</x-boton>
                        </div>
                    @else
                        <p class="py-6 text-center text-sm text-suave">Elige un paciente para ver su ficha.</p>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
