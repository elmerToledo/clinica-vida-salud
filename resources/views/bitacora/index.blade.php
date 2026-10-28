<x-app-layout>
    <x-slot name="header">
        <h2>Bitácora del <em>sistema</em></h2>
        <p class="mt-4 text-lg text-white/80">Registro de quién hizo qué y cuándo. Solo se consulta: no se puede modificar ni eliminar.</p>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-filtros>
                <div class="sm:col-span-2">
                    <x-input-label for="q" value="Buscar" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$filtros['q'] ?? ''" placeholder="Usuario o detalle" maxlength="100" />
                </div>
                <div>
                    <x-input-label for="modulo" value="Módulo" />
                    <select id="modulo" name="modulo" class="mt-1 campo">
                        <option value="">Todos</option>
                        @foreach (\App\Models\Bitacora::MODULOS as $clave => $nombre)
                            <option value="{{ $clave }}" @selected(($filtros['modulo'] ?? '') === $clave)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="accion" value="Acción" />
                    <select id="accion" name="accion" class="mt-1 campo">
                        <option value="">Todas</option>
                        @foreach (\App\Models\Bitacora::ACCIONES as $clave => $nombre)
                            <option value="{{ $clave }}" @selected(($filtros['accion'] ?? '') === $clave)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="desde" value="Desde" />
                    <x-text-input id="desde" name="desde" type="date" class="mt-1 block w-full" :value="$filtros['desde'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="hasta" value="Hasta" />
                    <x-text-input id="hasta" name="hasta" type="date" class="mt-1 block w-full" :value="$filtros['hasta'] ?? ''" />
                </div>
                <div class="flex gap-2 sm:col-span-2">
                    <x-boton>Buscar</x-boton>
                    <x-boton tipo="secundario" :href="route('bitacora.index')">Limpiar</x-boton>
                </div>
            </x-filtros>

            <x-tabla>
                <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
                    <tr>
                        <th class="px-5 py-4">Fecha y hora</th>
                        <th class="px-5 py-4">Usuario</th>
                        <th class="px-5 py-4">Acción</th>
                        <th class="px-5 py-4 hidden md:table-cell">Módulo</th>
                        <th class="px-5 py-4 hidden sm:table-cell">Detalle</th>
                        <th class="px-5 py-4 hidden lg:table-cell">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($registros as $registro)
                        @php
                            $tono = match ($registro->accion) {
                                'ingreso', 'activar', 'confirmar', 'asistencia', 'atender' => 'bg-green-100 text-ok',
                                'ingreso_fallido', 'desactivar', 'cancelar', 'inasistencia' => 'bg-red-100 text-red-800',
                                'modificar', 'reprogramar' => 'bg-ambar-claro text-tinta',
                                'crear' => 'bg-teal-100 text-deep',
                                default => 'bg-gray-200 text-gray-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-5 py-4 whitespace-nowrap">{{ $registro->fecha_hora->timezone('America/La_Paz')->format('d/m/Y H:i:s') }}</td>
                            <td class="px-5 py-4 whitespace-nowrap font-medium">{{ $registro->usuario ?: '—' }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $tono }}">{{ \App\Models\Bitacora::ACCIONES[$registro->accion] ?? $registro->accion }}</span></td>
                            <td class="px-5 py-4 hidden md:table-cell">{{ \App\Models\Bitacora::MODULOS[$registro->modulo] ?? $registro->modulo }}</td>
                            <td class="px-5 py-4 hidden sm:table-cell">{{ $registro->detalle }}</td>
                            <td class="px-5 py-4 hidden lg:table-cell whitespace-nowrap">{{ $registro->ip }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500">No hay registros con esos filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-tabla>

            <div>{{ $registros->links() }}</div>
        </div>
    </div>
</x-app-layout>
