<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Citas por fecha</h2>
                <p>Citas programadas y confirmadas entre dos fechas.</p>
            </div>
            <x-boton class="no-imprimir" tipo="secundario" :href="route('reportes.index')">Todos los reportes</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <x-reporte-filtro ruta="reportes.fecha" :desde="$desde" :hasta="$hasta" />

            <div class="card-soft p-5">
                <p class="text-sm text-suave">Del {{ \Carbon\CarbonImmutable::parse($desde)->format('d/m/Y') }} al {{ \Carbon\CarbonImmutable::parse($hasta)->format('d/m/Y') }}</p>
                <p class="mt-1 text-3xl font-bold text-tinta">{{ $citas->count() }} <span class="text-base font-semibold text-suave">{{ $citas->count() === 1 ? 'cita' : 'citas' }}</span></p>
            </div>

            @forelse ($porDia as $dia => $delDia)
                <section class="space-y-2">
                    <h3 class="font-bold text-tinta">
                        {{ \App\Models\HorarioAtencion::DIAS[\Carbon\CarbonImmutable::parse($dia)->dayOfWeekIso] }}
                        {{ \Carbon\CarbonImmutable::parse($dia)->format('d/m/Y') }}
                        <span class="text-sm font-semibold text-suave">· {{ $delDia->count() }}</span>
                    </h3>
                    <x-tabla>
                        <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
                            <tr>
                                <th class="px-5 py-3">Hora</th>
                                <th class="px-5 py-3">Código</th>
                                <th class="px-5 py-3">Paciente</th>
                                <th class="px-5 py-3 hidden md:table-cell">Médico</th>
                                <th class="px-5 py-3 hidden md:table-cell">Especialidad</th>
                                <th class="px-5 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-linea">
                            @foreach ($delDia as $cita)
                                <tr>
                                    <td class="px-5 py-3 whitespace-nowrap">{{ $cita->inicio }} – {{ $cita->fin }}</td>
                                    <td class="px-5 py-3 whitespace-nowrap"><a href="{{ route('citas.show', $cita) }}" class="font-semibold text-primary hover:underline">{{ $cita->codigo }}</a></td>
                                    <td class="px-5 py-3">{{ $cita->paciente?->nombre_completo }}</td>
                                    <td class="px-5 py-3 hidden md:table-cell">{{ $cita->medico?->nombre_completo }}</td>
                                    <td class="px-5 py-3 hidden md:table-cell">{{ $cita->especialidad?->nombre }}</td>
                                    <td class="px-5 py-3"><x-cita-estado :estado="$cita->estado" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-tabla>
                </section>
            @empty
                <p class="card-soft p-8 text-center text-sm text-suave">No hay citas programadas ni confirmadas en esas fechas.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
