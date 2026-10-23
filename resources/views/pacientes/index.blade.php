<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>Registro de <em>pacientes</em></h2>
            <x-boton :href="route('pacientes.create')">Nuevo paciente</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
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

            <x-tabla>
                <thead class="bg-ambar-claro/40 text-left text-xs font-bold uppercase tracking-wider text-deep">
                    <tr>
                        <th class="px-5 py-4">Código</th>
                        <th class="px-5 py-4">CI</th>
                        <th class="px-5 py-4">Paciente</th>
                        <th class="px-5 py-4 hidden md:table-cell">Edad</th>
                        <th class="px-5 py-4 hidden sm:table-cell">Teléfono</th>
                        <th class="px-5 py-4">Estado</th>
                        <th class="px-5 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($pacientes as $paciente)
                        <tr class="{{ $paciente->activo ? '' : 'opacity-60' }}">
                            <td class="px-5 py-4 whitespace-nowrap">{{ $paciente->codigo }}</td>
                            <td class="px-5 py-4 whitespace-nowrap">{{ $paciente->ci }}</td>
                            <td class="px-5 py-4 font-medium">{{ $paciente->apellidos }}, {{ $paciente->nombres }}</td>
                            <td class="px-5 py-4 hidden md:table-cell">{{ $paciente->fecha_nacimiento->age }} años</td>
                            <td class="px-5 py-4 hidden sm:table-cell whitespace-nowrap">{{ $paciente->telefono }}</td>
                            <td class="px-5 py-4"><x-estado-badge :activo="$paciente->activo" /></td>
                            <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
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
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron pacientes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-tabla>

            <div>{{ $pacientes->links() }}</div>
        </div>
    </div>
</x-app-layout>
