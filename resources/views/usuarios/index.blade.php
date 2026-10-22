<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Usuarios y roles</h2>
            <x-boton :href="route('usuarios.create')">Nuevo usuario</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <!-- CU04: búsqueda y filtros -->
            <x-filtros>
                <div>
                    <x-input-label for="q" value="Buscar" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="request('q')" placeholder="Nombre, usuario o correo" maxlength="100" />
                </div>
                <div>
                    <x-input-label for="rol" value="Rol" />
                    <select id="rol" name="rol" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Todos</option>
                        @foreach (\App\Models\User::ROLES as $valor => $nombre)
                            <option value="{{ $valor }}" @selected($rol === $valor)>{{ $nombre }}</option>
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
                    <x-boton tipo="secundario" :href="route('usuarios.index')">Limpiar</x-boton>
                </div>
            </x-filtros>

            <x-tabla>
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3 hidden md:table-cell">Correo</th>
                        <th class="px-4 py-3">Rol</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($usuarios as $usuario)
                        <tr class="{{ $usuario->activo ? '' : 'bg-gray-50 text-gray-500' }}">
                            <td class="px-4 py-3 font-medium">{{ $usuario->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $usuario->usuario }}</td>
                            <td class="px-4 py-3 hidden md:table-cell">{{ $usuario->email }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $usuario->rol_nombre }}</td>
                            <td class="px-4 py-3"><x-estado-badge :activo="$usuario->activo" /></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="font-medium text-teal-700 hover:text-teal-900">Editar</a>
                                @unless ($usuario->is(auth()->user()))
                                    <form method="POST" action="{{ route('usuarios.estado', $usuario) }}" class="inline"
                                          onsubmit="return confirm('{{ $usuario->activo ? '¿Desactivar este usuario? Ya no podrá iniciar sesión.' : '¿Activar este usuario?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="font-medium {{ $usuario->activo ? 'text-red-600 hover:text-red-800' : 'text-green-700 hover:text-green-900' }}">
                                            {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No se encontraron usuarios.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-tabla>

            <div>{{ $usuarios->links() }}</div>
        </div>
    </div>
</x-app-layout>
