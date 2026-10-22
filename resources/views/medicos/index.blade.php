<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Médicos</h2>
            @if (auth()->user()->tieneRol('administrador'))
                <x-boton :href="route('medicos.create')">Nuevo médico</x-boton>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <!-- CU13: búsqueda por nombre o especialidad -->
            <x-filtros>
                <div>
                    <x-input-label for="q" value="Buscar" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="request('q')" placeholder="Nombre o especialidad" maxlength="100" />
                </div>
                <div>
                    <x-input-label for="especialidad" value="Especialidad" />
                    <select id="especialidad" name="especialidad" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Todas</option>
                        @foreach ($especialidades as $especialidad)
                            <option value="{{ $especialidad->id }}" @selected((string) $especialidadId === (string) $especialidad->id)>{{ $especialidad->nombre }}</option>
                        @endforeach
                    </select>
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
                    <x-boton tipo="secundario" :href="route('medicos.index')">Limpiar</x-boton>
                </div>
            </x-filtros>

            <x-tabla>
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Médico</th>
                        <th class="px-4 py-3">Especialidades</th>
                        <th class="px-4 py-3 hidden md:table-cell">Registro profesional</th>
                        <th class="px-4 py-3 hidden lg:table-cell">Teléfono</th>
                        <th class="px-4 py-3">Estado</th>
                        @if (auth()->user()->tieneRol('administrador'))
                            <th class="px-4 py-3 text-right">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($medicos as $medico)
                        <tr class="{{ $medico->activo ? '' : 'bg-gray-50 text-gray-500' }}">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $medico->codigo }}</td>
                            <td class="px-4 py-3 font-medium">{{ $medico->apellidos }}, {{ $medico->nombres }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($medico->especialidades as $especialidad)
                                        <span class="rounded-full bg-teal-50 px-2 py-0.5 text-xs text-teal-800">{{ $especialidad->nombre }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell whitespace-nowrap">{{ $medico->registro_profesional }}</td>
                            <td class="px-4 py-3 hidden lg:table-cell whitespace-nowrap">{{ $medico->telefono }}</td>
                            <td class="px-4 py-3"><x-estado-badge :activo="$medico->activo" /></td>
                            @if (auth()->user()->tieneRol('administrador'))
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                                    <a href="{{ route('medicos.edit', $medico) }}" class="font-medium text-teal-700 hover:text-teal-900">Editar</a>
                                    <a href="{{ route('horarios.index', ['medico' => $medico->id]) }}" class="font-medium text-gray-600 hover:text-gray-900">Horarios</a>
                                    <form method="POST" action="{{ route('medicos.estado', $medico) }}" class="inline"
                                          onsubmit="return confirm('{{ $medico->activo ? '¿Desactivar este médico? Ya no recibirá citas nuevas; su historial se conserva.' : '¿Activar este médico?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="font-medium {{ $medico->activo ? 'text-red-600 hover:text-red-800' : 'text-green-700 hover:text-green-900' }}">
                                            {{ $medico->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron médicos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-tabla>

            <div>{{ $medicos->links() }}</div>
        </div>
    </div>
</x-app-layout>
