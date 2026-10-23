<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>Áreas de <em>especialidad</em></h2>
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
                    <select id="estado" name="estado" class="mt-1 campo">
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
                <thead class="bg-ambar-claro/40 text-left text-xs font-bold uppercase tracking-wider text-deep">
                    <tr>
                        <th class="px-5 py-4">Especialidad</th>
                        <th class="px-5 py-4 hidden md:table-cell">Descripción</th>
                        <th class="px-5 py-4 hidden sm:table-cell">Médicos activos</th>
                        <th class="px-5 py-4">Estado</th>
                        @if (auth()->user()->tieneRol('administrador'))
                            <th class="px-5 py-4 text-right">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($especialidades as $especialidad)
                        <tr class="{{ $especialidad->activo ? '' : 'opacity-60' }}">
                            <td class="px-5 py-4 font-medium">{{ $especialidad->nombre }}</td>
                            <td class="px-5 py-4 hidden md:table-cell">{{ $especialidad->descripcion }}</td>
                            <td class="px-5 py-4 hidden sm:table-cell">{{ $especialidad->medicos_activos_count }}</td>
                            <td class="px-5 py-4"><x-estado-badge :activo="$especialidad->activo" /></td>
                            @if (auth()->user()->tieneRol('administrador'))
                                <td class="px-5 py-4 text-right whitespace-nowrap space-x-1">
                                    <a href="{{ route('especialidades.edit', $especialidad) }}" class="accion">Editar</a>
                                    {{-- CU19: al desactivar se avisa cuántos médicos activos la atienden --}}
                                    <form method="POST" action="{{ route('especialidades.estado', $especialidad) }}" class="inline"
                                          onsubmit="return confirm('{{ $especialidad->activo
                                              ? ($especialidad->medicos_activos_count > 0
                                                  ? '¿Desactivar? Tiene ' . $especialidad->medicos_activos_count . ' médico(s) activo(s) asociado(s).'
                                                  : '¿Desactivar esta especialidad?')
                                              : '¿Activar esta especialidad?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="accion {{ $especialidad->activo ? 'accion-peligro' : 'accion-ok' }}">
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
