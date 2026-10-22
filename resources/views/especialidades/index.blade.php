<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Especialidades médicas</h2>
            @if (auth()->user()->tieneRol('administrador'))
                <x-boton :href="route('especialidades.create')">Nueva especialidad</x-boton>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-filtros>
                <div class="sm:col-span-2">
                    <x-input-label for="q" value="Buscar" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="request('q')" placeholder="Nombre de la especialidad" maxlength="100" />
                </div>
                <div>
                    <x-input-label for="estado" value="Estado" />
                    <select id="estado" name="estado" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="todos" @selected($estado === 'todos')>Todas</option>
                        <option value="activos" @selected($estado === 'activos')>Activas</option>
                        <option value="inactivos" @selected($estado === 'inactivos')>Inactivas</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-boton>Buscar</x-boton>
                    <x-boton tipo="secundario" :href="route('especialidades.index')">Limpiar</x-boton>
                </div>
            </x-filtros>

            <x-tabla>
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Especialidad</th>
                        <th class="px-4 py-3 hidden md:table-cell">Descripción</th>
                        <th class="px-4 py-3 hidden sm:table-cell">Médicos activos</th>
                        <th class="px-4 py-3">Estado</th>
                        @if (auth()->user()->tieneRol('administrador'))
                            <th class="px-4 py-3 text-right">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($especialidades as $especialidad)
                        <tr class="{{ $especialidad->activo ? '' : 'bg-gray-50 text-gray-500' }}">
                            <td class="px-4 py-3 font-medium">{{ $especialidad->nombre }}</td>
                            <td class="px-4 py-3 hidden md:table-cell">{{ $especialidad->descripcion }}</td>
                            <td class="px-4 py-3 hidden sm:table-cell">{{ $especialidad->medicos_activos_count }}</td>
                            <td class="px-4 py-3"><x-estado-badge :activo="$especialidad->activo" /></td>
                            @if (auth()->user()->tieneRol('administrador'))
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                                    <a href="{{ route('especialidades.edit', $especialidad) }}" class="font-medium text-teal-700 hover:text-teal-900">Editar</a>
                                    {{-- CU19: al desactivar se avisa cuántos médicos activos la atienden --}}
                                    <form method="POST" action="{{ route('especialidades.estado', $especialidad) }}" class="inline"
                                          onsubmit="return confirm('{{ $especialidad->activo
                                              ? ($especialidad->medicos_activos_count > 0
                                                  ? '¿Desactivar? Tiene ' . $especialidad->medicos_activos_count . ' médico(s) activo(s) asociado(s).'
                                                  : '¿Desactivar esta especialidad?')
                                              : '¿Activar esta especialidad?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="font-medium {{ $especialidad->activo ? 'text-red-600 hover:text-red-800' : 'text-green-700 hover:text-green-900' }}">
                                            {{ $especialidad->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No se encontraron especialidades.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-tabla>

            <div>{{ $especialidades->links() }}</div>
        </div>
    </div>
</x-app-layout>
