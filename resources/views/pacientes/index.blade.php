<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pacientes</h2>
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
                    <select id="estado" name="estado" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">CI</th>
                        <th class="px-4 py-3">Paciente</th>
                        <th class="px-4 py-3 hidden md:table-cell">Edad</th>
                        <th class="px-4 py-3 hidden sm:table-cell">Teléfono</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pacientes as $paciente)
                        <tr class="{{ $paciente->activo ? '' : 'bg-gray-50 text-gray-500' }}">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $paciente->codigo }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $paciente->ci }}</td>
                            <td class="px-4 py-3 font-medium">{{ $paciente->apellidos }}, {{ $paciente->nombres }}</td>
                            <td class="px-4 py-3 hidden md:table-cell">{{ $paciente->fecha_nacimiento->age }} años</td>
                            <td class="px-4 py-3 hidden sm:table-cell whitespace-nowrap">{{ $paciente->telefono }}</td>
                            <td class="px-4 py-3"><x-estado-badge :activo="$paciente->activo" /></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                                <a href="{{ route('pacientes.edit', $paciente) }}" class="font-medium text-teal-700 hover:text-teal-900">Editar</a>
                                @if (auth()->user()->tieneRol('administrador'))
                                    <form method="POST" action="{{ route('pacientes.estado', $paciente) }}" class="inline"
                                          onsubmit="return confirm('{{ $paciente->activo ? '¿Desactivar este paciente? Su historial se conserva.' : '¿Activar este paciente?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="font-medium {{ $paciente->activo ? 'text-red-600 hover:text-red-800' : 'text-green-700 hover:text-green-900' }}">
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
