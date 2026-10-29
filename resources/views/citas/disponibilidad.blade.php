@php
    $esRecepcion = auth()->user()->tieneRol('recepcionista');
    $nombrePaciente = $esRecepcion ? $pacienteElegido?->nombre_completo : auth()->user()->paciente?->nombre_completo;
    $parametros = array_filter(['paciente' => $pacienteElegido?->id, 'especialidad' => $especialidad?->id]);
    $fechaObj = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $fecha);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Nueva cita</h2>
                <p>Complete los tres pasos para agendar la cita.</p>
            </div>
            @if ($esRecepcion)
                <x-boton tipo="secundario" :href="route('citas.index')">Ver agenda</x-boton>
            @elseif (auth()->user()->tieneRol('paciente'))
                <x-boton tipo="secundario" :href="route('mis-citas.index')">Mis citas</x-boton>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 lg:grid lg:grid-cols-[11rem_minmax(0,1fr)_19rem] gap-6 space-y-5 lg:space-y-0 items-start">
            <x-pasos-cita :paso="2" :paciente="$nombrePaciente" />

            <section class="card-soft overflow-hidden">
                <h3 class="border-b border-linea px-5 py-4 font-bold text-tinta">Paso 2: Médico y horario</h3>

                <div class="p-5 space-y-6">
                    @if ($sinPerfil)
                        <div class="rounded-2xl border border-linea bg-tenue p-4 text-sm text-tinta">
                            Para reservar necesita completar sus datos de paciente.
                            <a href="{{ route('perfil.edit') }}" class="accion">Completar mi perfil</a>
                        </div>
                    @endif

                    @if ($esRecepcion && $pacienteElegido)
                        <p class="text-sm text-suave">Paciente: <span class="font-semibold text-tinta">{{ $pacienteElegido->nombre_completo }}</span>
                            · <a href="{{ route('citas.paciente') }}" class="font-semibold text-primary hover:underline">Cambiar</a></p>
                    @endif

                    <!-- Especialidad -->
                    <div>
                        <p class="mb-2 text-sm font-semibold text-tinta">Especialidad</p>
                        <div class="flex flex-wrap gap-2">
                            @php
                                $chip = 'inline-flex items-center rounded-full border px-4 py-1.5 text-sm font-semibold';
                            @endphp
                            <a href="{{ route('citas.disponibilidad', array_filter(['paciente' => $pacienteElegido?->id, 'fecha' => $fecha])) }}"
                               class="{{ $chip }} {{ ! $especialidad ? 'border-primary bg-primary text-sobre-primary' : 'border-linea text-tinta hover:bg-tenue' }}">Todas</a>
                            @foreach ($especialidades as $e)
                                <a href="{{ route('citas.disponibilidad', array_filter(['paciente' => $pacienteElegido?->id, 'especialidad' => $e->id, 'fecha' => $fecha])) }}"
                                   class="{{ $chip }} {{ $especialidad?->id === $e->id ? 'border-primary bg-primary text-sobre-primary' : 'border-linea text-tinta hover:bg-tenue' }}">{{ $e->nombre }}</a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Médico -->
                    <div>
                        <p class="mb-2 text-sm font-semibold text-tinta">Médico</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @forelse ($medicos as $m)
                                <a href="{{ route('citas.disponibilidad', $parametros + ['medico' => $m->id, 'fecha' => $fecha]) }}"
                                   class="flex items-center gap-3 rounded-xl border p-3 {{ $medico?->id === $m->id ? 'border-primary bg-primary/10 ring-1 ring-primary' : 'border-linea hover:bg-tenue' }}">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-primary/15 text-sm font-bold text-primary">{{ mb_strtoupper(mb_substr($m->nombres, 0, 1).mb_substr($m->apellidos, 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold text-tinta">{{ $m->nombres }} {{ $m->apellidos }}</span>
                                        <span class="block truncate text-xs text-suave">{{ $m->especialidades->pluck('nombre')->join(', ') ?: 'Sin especialidad' }} · MP-{{ $m->registro_profesional }}</span>
                                    </span>
                                </a>
                            @empty
                                <p class="text-sm text-suave sm:col-span-2">No hay médicos activos para esa especialidad.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Fecha -->
                    <form method="GET" action="{{ route('citas.disponibilidad') }}" class="flex flex-wrap items-end gap-3">
                        @foreach ($parametros as $clave => $valor)
                            <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                        @endforeach
                        @if ($medico)
                            <input type="hidden" name="medico" value="{{ $medico->id }}">
                        @endif
                        <div>
                            <x-input-label for="fecha" value="Fecha" />
                            <input id="fecha" name="fecha" type="date" value="{{ $fecha }}" min="{{ $hoy }}" class="mt-1 campo" required>
                        </div>
                        <x-boton tipo="secundario">Consultar</x-boton>
                    </form>

                    <!-- Franjas -->
                    <div>
                        <p class="mb-2 text-sm font-semibold text-tinta">Franja horaria</p>

                        @if (! $medico)
                            <p class="rounded-xl border border-dashed border-linea p-6 text-center text-sm text-suave">Elija un médico y una fecha para ver los horarios libres.</p>
                        @else
                            @php
                                $libres = $bloques->where('estado', 'libre')->count();
                                $diaNombre = \App\Models\HorarioAtencion::DIAS[$fechaObj->dayOfWeekIso];
                            @endphp
                            <p class="mb-3 text-sm text-suave">
                                {{ $medico->nombres }} {{ $medico->apellidos }} · {{ $diaNombre }} {{ $fechaObj->format('d/m/Y') }}
                                · {{ $libres }} {{ $libres === 1 ? 'bloque libre' : 'bloques libres' }}
                            </p>

                            @if ($bloques->isEmpty())
                                <p class="rounded-xl border border-dashed border-linea p-6 text-center text-sm text-suave">El médico no tiene horario de atención ese día.</p>
                            @else
                                <div class="grid gap-2 grid-cols-3 sm:grid-cols-4 md:grid-cols-6">
                                    @foreach ($bloques as $bloque)
                                        @if ($bloque['estado'] === 'libre' && $puedeReservar)
                                            <a href="{{ route('citas.create', $parametros + ['medico' => $medico->id, 'fecha' => $fecha, 'hora' => $bloque['inicio']]) }}"
                                               title="Libre · Reservar"
                                               class="rounded-xl border border-linea px-2 py-2.5 text-center text-sm font-semibold text-tinta hover:border-primary hover:bg-primary hover:text-sobre-primary transition">{{ $bloque['inicio'] }}</a>
                                        @elseif ($bloque['estado'] === 'libre')
                                            <span class="rounded-xl border border-linea px-2 py-2.5 text-center text-sm font-semibold text-tinta">{{ $bloque['inicio'] }}</span>
                                        @else
                                            <span title="{{ $bloque['estado'] === 'ocupado' ? 'Ocupado' : 'Ya pasó' }}" class="rounded-xl border border-linea bg-tenue px-2 py-2.5 text-center text-sm text-suave line-through">{{ $bloque['inicio'] }}</span>
                                        @endif
                                    @endforeach
                                </div>
                                <p class="mt-3 text-xs text-suave">Pulse una franja libre para continuar con la reserva. Libre · Reservar.</p>
                            @endif
                        @endif
                    </div>
                </div>

                @if ($esRecepcion)
                    <div class="border-t border-linea px-5 py-4">
                        <x-boton tipo="secundario" :href="route('citas.paciente')">Anterior</x-boton>
                    </div>
                @endif
            </section>

            <x-info-cita />
        </div>
    </div>
</x-app-layout>
