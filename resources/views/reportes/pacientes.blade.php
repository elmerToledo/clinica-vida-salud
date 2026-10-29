<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Pacientes registrados</h2>
                <p>Altas de pacientes por fecha de registro. Sin fechas, se muestran todos.</p>
            </div>
            <x-boton class="no-imprimir" tipo="secundario" :href="route('reportes.index')">Todos los reportes</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <x-reporte-filtro ruta="reportes.pacientes" :desde="$desde" :hasta="$hasta" :opcional="true">
                <div>
                    <x-input-label for="estado" value="Estado" />
                    <select id="estado" name="estado" class="mt-1 campo">
                        <option value="todos" @selected($estado === 'todos')>Todos</option>
                        <option value="activos" @selected($estado === 'activos')>Activos</option>
                        <option value="inactivos" @selected($estado === 'inactivos')>Inactivos</option>
                    </select>
                </div>
            </x-reporte-filtro>

            <div class="grid grid-cols-3 gap-3 sm:gap-4">
                @foreach ([['Total', $resumen['total']], ['Activos', $resumen['activos']], ['Inactivos', $resumen['inactivos']]] as [$etiqueta, $valor])
                    <div class="card-soft p-4 sm:p-5">
                        <p class="text-sm text-suave">{{ $etiqueta }}</p>
                        <p class="mt-1 text-3xl font-bold text-tinta">{{ $valor }}</p>
                    </div>
                @endforeach
            </div>

            <x-tabla>
                <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
                    <tr>
                        <th class="px-5 py-4">Código</th>
                        <th class="px-5 py-4">Paciente</th>
                        <th class="px-5 py-4 hidden sm:table-cell">CI</th>
                        <th class="px-5 py-4 hidden md:table-cell">Registrado</th>
                        <th class="px-5 py-4">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($pacientes as $paciente)
                        <tr class="{{ $paciente->activo ? '' : 'opacity-60' }}">
                            <td class="px-5 py-3 whitespace-nowrap">{{ $paciente->codigo }}</td>
                            <td class="px-5 py-3 font-semibold text-tinta">{{ $paciente->apellidos }}, {{ $paciente->nombres }}</td>
                            <td class="px-5 py-3 hidden sm:table-cell whitespace-nowrap">{{ $paciente->ci }}</td>
                            <td class="px-5 py-3 hidden md:table-cell whitespace-nowrap">{{ $paciente->created_at?->format('d/m/Y') }}</td>
                            <td class="px-5 py-3"><x-estado-badge :activo="$paciente->activo" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-suave">No hay pacientes con esos filtros.</td></tr>
                    @endforelse
                </tbody>
            </x-tabla>

            <div class="no-imprimir">{{ $pacientes->links() }}</div>
        </div>
    </div>
</x-app-layout>
