<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Citas por estado</h2>
                <p>Cuántas citas hay en cada estado dentro del período.</p>
            </div>
            <x-boton class="no-imprimir" tipo="secundario" :href="route('reportes.index')">Todos los reportes</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <x-reporte-filtro ruta="reportes.estado" :desde="$desde" :hasta="$hasta" />

            <div class="card-soft p-5">
                <p class="text-sm text-suave">Total de citas del período</p>
                <p class="mt-1 text-3xl font-bold text-tinta">{{ $total }}</p>
            </div>

            <x-tabla>
                <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
                    <tr>
                        <th class="px-5 py-4">Estado</th>
                        <th class="px-5 py-4 text-right">Citas</th>
                        <th class="px-5 py-4 w-1/2 hidden sm:table-cell">Proporción</th>
                        <th class="px-5 py-4 text-right no-imprimir">Agenda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @foreach ($filas as $fila)
                        @php
                            $porcentaje = $total > 0 ? round($fila['total'] * 100 / $total) : 0;
                        @endphp
                        <tr>
                            <td class="px-5 py-3"><x-cita-estado :estado="$fila['estado']" /></td>
                            <td class="px-5 py-3 text-right font-bold text-tinta">{{ $fila['total'] }}</td>
                            <td class="px-5 py-3 hidden sm:table-cell">
                                <div class="flex items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-tenue"><div class="h-2 rounded-full bg-primary" style="width: {{ $porcentaje }}%"></div></div>
                                    <span class="w-10 text-right text-sm text-suave">{{ $porcentaje }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right no-imprimir">
                                @if ($fila['total'] > 0)
                                    <a href="{{ route('citas.index', ['estado' => $fila['estado'], 'desde' => $desde, 'hasta' => $hasta]) }}" class="accion">Ver citas</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-tabla>
        </div>
    </div>
</x-app-layout>
