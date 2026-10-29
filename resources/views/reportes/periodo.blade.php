<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Atendidas, canceladas e inasistencias</h2>
                <p>Resultado de las citas del período.</p>
            </div>
            <x-boton class="no-imprimir" tipo="secundario" :href="route('reportes.index')">Todos los reportes</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <x-reporte-filtro ruta="reportes.periodo" :desde="$desde" :hasta="$hasta" />

            <div class="grid gap-3 sm:grid-cols-3 sm:gap-4">
                @foreach ([['Atendidas', 'atendidas', 'text-[rgb(var(--c-info))]'], ['Canceladas', 'canceladas', 'text-[rgb(var(--c-peligro))]'], ['Inasistencias', 'inasistencias', 'text-suave']] as [$etiqueta, $clave, $color])
                    @php
                        $porcentaje = $datos['total'] > 0 ? round($datos[$clave] * 100 / $datos['total']) : 0;
                    @endphp
                    <div class="card-soft p-5">
                        <p class="text-sm text-suave">{{ $etiqueta }}</p>
                        <p class="mt-1 text-4xl font-bold {{ $color }}">{{ $datos[$clave] }}</p>
                        <p class="mt-1 text-sm text-suave">{{ $porcentaje }}% de las {{ $datos['total'] }} citas con resultado</p>
                    </div>
                @endforeach
            </div>

            <p class="text-sm text-suave">Se cuentan solo las citas que ya tuvieron un resultado (atendida, cancelada o inasistencia). Las programadas y confirmadas no entran.</p>
        </div>
    </div>
</x-app-layout>
