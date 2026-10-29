<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Citas por médico</h2>
                <p>Cuántas citas tiene cada médico en el período, según su estado.</p>
            </div>
            <x-boton class="no-imprimir" tipo="secundario" :href="route('reportes.index')">Todos los reportes</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <x-reporte-filtro ruta="reportes.medico" :desde="$desde" :hasta="$hasta" />

            <x-tabla>
                <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
                    <tr>
                        <th class="px-5 py-4">Médico</th>
                        <th class="px-5 py-4 text-right">Total</th>
                        @foreach (\App\Models\Cita::ESTADOS as $nombre)
                            <th class="px-5 py-4 text-right hidden md:table-cell">{{ $nombre }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($filas as $fila)
                        <tr>
                            <td class="px-5 py-3 font-semibold text-tinta">{{ $fila->apellidos }}, {{ $fila->nombres }}</td>
                            <td class="px-5 py-3 text-right font-bold text-tinta">{{ $fila->total }}</td>
                            @foreach (array_keys(\App\Models\Cita::ESTADOS) as $estado)
                                <td class="px-5 py-3 text-right hidden md:table-cell">{{ $fila->$estado }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-suave">No hay datos para mostrar.</td></tr>
                    @endforelse
                </tbody>
                @if ($filas->isNotEmpty())
                    <tfoot class="border-t border-linea bg-tenue font-bold text-tinta">
                        <tr>
                            <td class="px-5 py-3">Total</td>
                            <td class="px-5 py-3 text-right">{{ $filas->sum('total') }}</td>
                            @foreach (array_keys(\App\Models\Cita::ESTADOS) as $estado)
                                <td class="px-5 py-3 text-right hidden md:table-cell">{{ $filas->sum($estado) }}</td>
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </x-tabla>
        </div>
    </div>
</x-app-layout>
