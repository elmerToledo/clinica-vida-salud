<x-app-layout>
    <x-slot name="header">
        <h2>Médicos y horarios</h2>
        <p>Vea quién atiende cada especialidad y en qué días y horas.</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @php
                $chip = 'inline-flex items-center rounded-full border px-4 py-1.5 text-sm font-semibold';
            @endphp
            <div class="flex flex-wrap gap-2" aria-label="Filtrar por especialidad">
                <a href="{{ route('directorio.index') }}" class="{{ $chip }} {{ ! $especialidad ? 'border-primary bg-primary text-sobre-primary' : 'border-linea text-tinta hover:bg-tenue' }}">Todas</a>
                @foreach ($especialidades as $e)
                    <a href="{{ route('directorio.index', ['especialidad' => $e->id]) }}" class="{{ $chip }} {{ $especialidad?->id === $e->id ? 'border-primary bg-primary text-sobre-primary' : 'border-linea text-tinta hover:bg-tenue' }}">{{ $e->nombre }}</a>
                @endforeach
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($medicos as $medico)
                    <article class="card-soft flex flex-col gap-4 p-5">
                        <div class="flex items-start gap-3">
                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-primary/15 text-lg font-bold text-primary" aria-hidden="true">{{ mb_strtoupper(mb_substr($medico->nombres, 0, 1).mb_substr($medico->apellidos, 0, 1)) }}</span>
                            <div class="min-w-0">
                                <h3 class="truncate font-bold text-tinta">{{ $medico->nombres }} {{ $medico->apellidos }}</h3>
                                <p class="text-sm text-suave">MP-{{ $medico->registro_profesional }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($medico->especialidades as $e)
                                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">{{ $e->nombre }}</span>
                            @endforeach
                        </div>

                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-suave">Horarios de atención</p>
                            @php
                                $porDia = $medico->horarios->groupBy('dia_semana');
                            @endphp
                            @forelse ($porDia as $dia => $franjas)
                                <div class="flex items-start justify-between gap-3 border-b border-linea py-1.5 text-sm last:border-0">
                                    <span class="font-semibold text-tinta">{{ \App\Models\HorarioAtencion::DIAS[$dia] }}</span>
                                    <span class="text-right text-suave">{{ $franjas->map(fn ($f) => substr($f->hora_inicio, 0, 5).' – '.substr($f->hora_fin, 0, 5))->join(', ') }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-suave">Sin horarios registrados.</p>
                            @endforelse
                        </div>

                        @if ($puedeReservar && $medico->horarios->isNotEmpty())
                            <div class="mt-auto border-t border-linea pt-4">
                                <x-boton :href="route('citas.disponibilidad', ['medico' => $medico->id])">Ver horarios libres</x-boton>
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="card-soft p-8 text-center text-sm text-suave md:col-span-2 xl:col-span-3">No hay médicos activos para esa especialidad.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
