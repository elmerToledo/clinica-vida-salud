@php
    $fecha = \Illuminate\Support\Str::ucfirst(now()->timezone('America/La_Paz')->locale('es')->translatedFormat('l j \d\e F \d\e Y'));
    $iconos = ['usuarios.index' => 'lock', 'pacientes.index' => 'users', 'medicos.index' => 'stethoscope', 'especialidades.index' => 'heart', 'horarios.index' => 'clock', 'bitacora.index' => 'file-text', 'citas.index' => 'calendar-check', 'citas.disponibilidad' => 'calendar-check', 'mis-citas.index' => 'file-text', 'perfil.edit' => 'user-check', 'directorio.index' => 'stethoscope'];
    $primerNombre = explode(' ', trim($usuario->name))[0];
    // Colores por estado de cita (bloques de la línea de tiempo)
    $bloque = [
        'programada' => 'bg-[rgb(var(--c-warn)/.18)] text-[rgb(var(--c-warn))] border-[rgb(var(--c-warn))]',
        'confirmada' => 'bg-[rgb(var(--c-ok)/.18)] text-[rgb(var(--c-ok))] border-[rgb(var(--c-ok))]',
        'atendida' => 'bg-[rgb(var(--c-info)/.2)] text-[rgb(var(--c-info))] border-[rgb(var(--c-info))]',
        'cancelada' => 'bg-[rgb(var(--c-peligro)/.16)] text-[rgb(var(--c-peligro))] border-[rgb(var(--c-peligro))] line-through',
        'inasistencia' => 'bg-tenue text-suave border-suave',
    ];
    $iniciales = fn (string $n) => mb_strtoupper(mb_substr($n, 0, 1) . mb_substr(strstr($n, ' ') ?: ' ', 1, 1));
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ $saludo }}, {{ $primerNombre }}</h2>
        <p>{{ $usuario->rol_nombre }} · {{ $fecha }}</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Tarjetas de resumen -->
            @if (count($resumen) > 0)
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen">
                    @foreach ($resumen as [$etiqueta, $total, $icono])
                        <div class="card-soft p-5">
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-sm font-medium text-suave">{{ $etiqueta }}</span>
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"><x-icono :nombre="$icono" class="h-5 w-5" /></span>
                            </div>
                            <div class="mt-2 text-4xl font-bold leading-none text-tinta"><x-contador :valor="$total" /></div>
                        </div>
                    @endforeach
                </section>
            @endif

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0 space-y-6">

                    <!-- Agenda de hoy por médico -->
                    @if ($lineaTiempo)
                        @php
                            $d = $lineaTiempo['desde'];
                            $h = $lineaTiempo['hasta'];
                            $pos = fn (string $hm) => max(0, min(100, ((((int) substr($hm, 0, 2)) * 60 + (int) substr($hm, 3, 2)) - $d * 60) / (($h - $d) * 60) * 100));
                        @endphp
                        <section class="card-soft overflow-hidden" aria-label="Agenda de hoy por médico">
                            <header class="border-b border-linea px-5 py-4">
                                <h3 class="text-base font-bold text-tinta">Agenda de hoy por médico</h3>
                                <p class="text-sm text-suave">{{ $fecha }} · {{ sprintf('%02d:00', $d) }} a {{ sprintf('%02d:00', $h) }}</p>
                            </header>
                            <div class="overflow-x-auto">
                                <div class="min-w-[680px] p-5">
                                    <div class="grid grid-cols-[10rem_1fr] gap-x-3">
                                        <div></div>
                                        <div class="relative h-6 text-xs text-suave">
                                            @foreach (range($d, $h) as $i)
                                                <span class="absolute -translate-x-1/2" style="left: {{ ($i - $d) / ($h - $d) * 100 }}%">{{ sprintf('%02d:00', $i) }}</span>
                                            @endforeach
                                        </div>
                                        @forelse ($lineaTiempo['filas'] as $fila)
                                            <div class="flex items-center gap-2 py-1.5">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/15 text-xs font-bold text-primary" aria-hidden="true">{{ $iniciales($fila['medico']->nombres . ' ' . $fila['medico']->apellidos) }}</span>
                                                <span class="truncate text-sm font-semibold text-tinta">{{ $fila['medico']->apellidos }}</span>
                                            </div>
                                            <div class="relative my-1.5 h-10 rounded-lg bg-tenue">
                                                @foreach (range(1, max(1, $h - $d - 1)) as $i)
                                                    <span class="absolute inset-y-0 w-px bg-linea" style="left: {{ $i / ($h - $d) * 100 }}%" aria-hidden="true"></span>
                                                @endforeach
                                                @foreach ($fila['citas'] as $c)
                                                    @php
                                                        $ancho = max(2.5, $pos($c->fin) - $pos($c->inicio));
                                                    @endphp
                                                    <a href="{{ route('citas.show', $c) }}" title="{{ $c->inicio }}–{{ $c->fin }} · {{ $c->paciente?->nombre_completo }} · {{ $c->estado_nombre }}"
                                                       class="absolute inset-y-1 flex flex-col justify-center overflow-hidden rounded-md border-l-4 px-1.5 text-[11px] font-semibold leading-tight hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $bloque[$c->estado] ?? $bloque['programada'] }}"
                                                       style="left: {{ $pos($c->inicio) }}%; width: {{ $ancho }}%">
                                                        <span>{{ $c->inicio }}</span>
                                                        <span class="truncate">{{ $c->paciente?->apellidos }}</span>
                                                    </a>
                                                @endforeach
                                                @if ($lineaTiempo['ahora'] !== null)
                                                    <span class="absolute inset-y-0 w-0.5 bg-[rgb(var(--c-peligro))]" style="left: {{ $lineaTiempo['ahora'] }}%" aria-hidden="true"></span>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="col-span-2 py-6 text-center text-sm text-suave">No hay médicos activos para mostrar.</p>
                                        @endforelse
                                    </div>
                                    @if ($lineaTiempo['ahora'] !== null)
                                        <p class="mt-2 text-xs text-suave">La línea roja marca la hora actual ({{ $lineaTiempo['horaAhora'] }}).</p>
                                    @endif
                                </div>
                            </div>
                        </section>
                    @endif

                    <!-- Próximas citas -->
                    <section class="card-soft overflow-hidden" aria-label="Próximas citas">
                        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-linea px-5 py-4">
                            <div>
                                <h3 class="text-base font-bold text-tinta">Próximas citas</h3>
                                <p class="text-sm text-suave">Citas programadas y su estado actual.</p>
                            </div>
                            <nav class="flex rounded-xl bg-tenue p-1 text-sm font-semibold" aria-label="Rango de fechas">
                                @foreach (['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes'] as $clave => $texto)
                                    <a href="{{ route('dashboard', ['rango' => $clave, 'mes' => request('mes')]) }}" @if ($rango === $clave) aria-current="true" @endif
                                       class="rounded-lg px-3 py-1.5 {{ $rango === $clave ? 'bg-tarjeta text-tinta shadow-sm' : 'text-suave hover:text-tinta' }}">{{ $texto }}</a>
                                @endforeach
                            </nav>
                        </header>
                        @if ($proximas->isEmpty())
                            <p class="px-5 py-10 text-center text-sm text-suave">No hay citas en este periodo.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-left text-sm">
                                    <thead class="text-xs uppercase tracking-wide">
                                        <tr>
                                            <th class="px-5 py-3">Fecha y hora</th>
                                            <th class="px-5 py-3">Paciente</th>
                                            <th class="px-5 py-3 hidden md:table-cell">Médico</th>
                                            <th class="px-5 py-3 hidden lg:table-cell">Especialidad</th>
                                            <th class="px-5 py-3">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-linea">
                                        @foreach ($proximas as $c)
                                            <tr class="hover:bg-tenue/60">
                                                <td class="whitespace-nowrap px-5 py-3 font-semibold text-tinta"><a href="{{ route('citas.show', $c) }}" class="hover:underline">{{ $c->fecha->format('d/m/Y') }} · {{ $c->inicio }}</a></td>
                                                <td class="px-5 py-3">
                                                    <div class="flex items-center gap-2.5">
                                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/15 text-xs font-bold text-primary" aria-hidden="true">{{ $iniciales($c->paciente?->nombre_completo ?? '?') }}</span>
                                                        <span class="font-medium text-tinta">{{ $c->paciente?->nombre_completo }}</span>
                                                    </div>
                                                </td>
                                                <td class="hidden px-5 py-3 text-tinta md:table-cell">{{ $c->medico?->nombre_completo }}</td>
                                                <td class="hidden px-5 py-3 text-suave lg:table-cell">{{ $c->especialidad?->nombre }}</td>
                                                <td class="px-5 py-3"><x-cita-estado :estado="$c->estado" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                </div>

                <!-- Columna derecha: calendario y avisos -->
                <aside class="space-y-6">
                    <section class="card-soft p-5" aria-label="Calendario">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-base font-bold text-tinta">{{ $calendario['titulo'] }}</h3>
                            <div class="flex gap-1">
                                <a href="{{ route('dashboard', ['rango' => $rango, 'mes' => $calendario['anterior']]) }}" aria-label="Mes anterior" class="flex h-8 w-8 items-center justify-center rounded-lg text-suave hover:bg-tenue"><x-icono nombre="chevron-down" class="h-4 w-4 rotate-90" /></a>
                                <a href="{{ route('dashboard', ['rango' => $rango, 'mes' => $calendario['siguiente']]) }}" aria-label="Mes siguiente" class="flex h-8 w-8 items-center justify-center rounded-lg text-suave hover:bg-tenue"><x-icono nombre="chevron-down" class="h-4 w-4 -rotate-90" /></a>
                            </div>
                        </div>
                        <div class="grid grid-cols-7 gap-y-1 text-center text-xs">
                            @foreach (['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'] as $dia)
                                <span class="py-1 font-semibold text-suave">{{ $dia }}</span>
                            @endforeach
                            @if ($calendario['vacios'] > 0)
                                @foreach (range(1, $calendario['vacios']) as $vacio)
                                    <span></span>
                                @endforeach
                            @endif
                            @foreach (range(1, $calendario['dias']) as $n)
                                @php
                                    $iso = $calendario['mes']->day($n)->format('Y-m-d');
                                    $esHoy = $iso === $hoy;
                                    $tiene = $calendario['conCitas']->has($iso);
                                    $clases = 'relative mx-auto flex h-8 w-8 items-center justify-center rounded-lg text-sm ' . ($esHoy ? 'bg-primary font-bold text-sobre-primary' : 'text-tinta hover:bg-tenue');
                                @endphp
                                @if ($puedeVerAgenda)
                                    <a href="{{ route('citas.index', ['desde' => $iso, 'hasta' => $iso]) }}" class="{{ $clases }}" @if ($esHoy) aria-current="date" @endif aria-label="{{ $n }}{{ $tiene ? ', con citas' : '' }}">
                                        {{ $n }}@if ($tiene)<span class="absolute bottom-0.5 h-1 w-1 rounded-full {{ $esHoy ? 'bg-sobre-primary' : 'bg-primary' }}"></span>@endif
                                    </a>
                                @else
                                    <span class="{{ $clases }}">{{ $n }}@if ($tiene)<span class="absolute bottom-0.5 h-1 w-1 rounded-full {{ $esHoy ? 'bg-sobre-primary' : 'bg-primary' }}"></span>@endif</span>
                                @endif
                            @endforeach
                        </div>
                        <p class="mt-3 text-xs text-suave">Los puntos marcan los días con citas.</p>
                    </section>

                    @if ($verTodo && count($avisos) > 0)
                        <section class="card-soft overflow-hidden" aria-label="Avisos recientes">
                            <header class="border-b border-linea px-5 py-4"><h3 class="text-base font-bold text-tinta">Avisos recientes</h3></header>
                            <ul class="divide-y divide-linea">
                                @foreach ($avisos as $a)
                                    @php
                                        [$ico, $tono] = match ($a['tipo']) {
                                            'alerta' => ['clock', 'bg-[rgb(var(--c-warn)/.18)] text-[rgb(var(--c-warn))]'],
                                            'cancelada' => ['x', 'bg-[rgb(var(--c-peligro)/.16)] text-[rgb(var(--c-peligro))]'],
                                            default => ['user-plus', 'bg-[rgb(var(--c-info)/.18)] text-[rgb(var(--c-info))]'],
                                        };
                                    @endphp
                                    <li class="flex gap-3 px-5 py-3.5">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $tono }}"><x-icono :nombre="$ico" class="h-4 w-4" /></span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-tinta">{{ $a['titulo'] }}</p>
                                            <p class="text-sm text-suave">{{ $a['detalle'] }}</p>
                                            @if ($a['cuando'])<p class="mt-0.5 text-xs text-suave">{{ $a['cuando'] }}</p>@endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($resumenHoy && $resumenHoy['total'] > 0)
                        <div class="card-soft flex items-center gap-3 p-4 text-sm text-tinta">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"><x-icono nombre="check-circle" class="h-4 w-4" /></span>
                            {{ $resumenHoy['atendidas'] }} {{ $resumenHoy['atendidas'] === 1 ? 'cita atendida' : 'citas atendidas' }} hoy de {{ $resumenHoy['total'] }} programadas.
                        </div>
                    @endif
                </aside>
            </div>

            <!-- Accesos (médico y paciente): el resto de roles usa el menú lateral -->
            @if (! $verTodo && count($accesos) > 0)
                <section>
                    <h3 class="mb-4 text-lg font-bold text-tinta">Accesos rápidos</h3>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($accesos as $acceso)
                            <a href="{{ route($acceso['ruta']) }}" class="card-soft card-lift group flex items-start gap-4 p-5">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"><x-icono :nombre="$iconos[$acceso['ruta']] ?? 'home'" class="h-5 w-5" /></span>
                                <span class="min-w-0">
                                    <span class="block text-base font-bold text-tinta">{{ $acceso['etiqueta'] }}</span>
                                    <span class="mt-0.5 block text-sm text-suave">{{ $acceso['descripcion'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
