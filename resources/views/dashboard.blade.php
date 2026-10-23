@php
    $fecha = \Illuminate\Support\Str::ucfirst(now()->timezone('America/La_Paz')->locale('es')->translatedFormat('l j \d\e F \d\e Y'));
    $iconos = ['usuarios.index' => 'lock', 'pacientes.index' => 'users', 'medicos.index' => 'stethoscope', 'especialidades.index' => 'heart', 'horarios.index' => 'clock', 'bitacora.index' => 'file-text', 'citas.index' => 'calendar-check', 'citas.disponibilidad' => 'calendar-check', 'mis-citas.index' => 'file-text', 'perfil.edit' => 'user-check'];
    $avisos = ['Horario de atención: ' . config('clinica.horario'), 'Verifique los datos del paciente al registrarlo', 'Los pacientes con cuenta pueden reservar su cita en línea'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-end md:min-h-[220px] md:max-w-[50%]">
            <h2>Bienvenido(a), <em>{{ $usuario->name }}</em></h2>
            <p class="mt-4 text-lg text-white/80">{{ $usuario->rol_nombre }} · {{ $fecha }}</p>
            @if ($usuario->tieneRol('administrador', 'recepcionista'))
                <div class="mt-8">
                    <x-boton :href="route('pacientes.create')">Nuevo paciente <x-icono nombre="arrow-right" class="h-5 w-5" /></x-boton>
                </div>
            @elseif ($usuario->tieneRol('paciente'))
                <div class="mt-8">
                    <x-boton :href="route('citas.disponibilidad')">Reservar cita <x-icono nombre="arrow-right" class="h-5 w-5" /></x-boton>
                </div>
            @endif
        </div>
        <!-- Fotos decorativas que se desplazan despacio (se pausan con el mouse) -->
        <div class="hero-photos" aria-hidden="true">
            <div class="hero-photos-track">
                @foreach ([1, 2, 3, 4, 1, 2, 3, 4] as $n)
                    <img src="{{ asset('img/panel/hero-' . $n . '.jpg') }}" alt="" loading="lazy" decoding="async" width="360" height="446">
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Resumen -->
            @if (count($resumen) > 0)
                <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen">
                    @foreach ($resumen as $etiqueta => $total)
                        @php
                            $icono = match (true) {
                                str_contains($etiqueta, 'Usuarios') => 'lock',
                                str_contains($etiqueta, 'Pacientes') => 'users',
                                str_contains($etiqueta, 'Médicos') => 'stethoscope',
                                str_contains($etiqueta, 'Especialidades') => 'heart',
                                default => 'clock',
                            };
                        @endphp
                        <div class="card-soft card-lift p-6">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-100 text-primary"><x-icono :nombre="$icono" class="h-6 w-6" /></div>
                            <div class="mt-5 font-display text-6xl font-extrabold leading-none text-deep"><x-contador :valor="$total" /></div>
                            <div class="mt-2 text-lg font-semibold text-suave">{{ $etiqueta }}</div>
                        </div>
                    @endforeach
                </section>
            @endif

            <!-- Franja de avisos -->
            <section class="overflow-hidden rounded-[22px] bg-deep py-4" aria-label="Avisos">
                <div class="marquee-track">
                    @foreach ([0, 1] as $copia)
                        @foreach ($avisos as $aviso)
                            <div class="mx-3 flex shrink-0 items-center gap-4" @if ($copia) aria-hidden="true" @endif>
                                <span class="text-lg font-semibold text-crema">{{ $aviso }}</span>
                                <span class="text-ambar">✦</span>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </section>

            <!-- Accesos según el rol -->
            @if (count($accesos) > 0)
                <section>
                    <h3 class="mb-5 text-3xl font-extrabold md:text-4xl">Accesos <em class="font-semibold text-primary">rápidos</em></h3>
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($accesos as $acceso)
                            <a href="{{ route($acceso['ruta']) }}" class="card-soft card-lift group flex flex-col gap-4 p-7">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-ambar-claro text-deep"><x-icono :nombre="$iconos[$acceso['ruta']] ?? 'home'" class="h-6 w-6" /></div>
                                <div>
                                    <div class="font-display text-2xl font-bold">{{ $acceso['etiqueta'] }}</div>
                                    <p class="mt-1 text-suave">{{ $acceso['descripcion'] }}</p>
                                </div>
                                <span class="mt-auto inline-flex items-center gap-2 font-semibold text-primary">Abrir <x-icono nombre="arrow-right" class="h-5 w-5 transition-transform duration-200 group-hover:translate-x-1" /></span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
