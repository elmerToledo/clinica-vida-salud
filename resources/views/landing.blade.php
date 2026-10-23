@php
    $c = config('clinica');
    $wa = preg_replace('/\D/', '', $c['whatsapp_numero'] ?? '');
    $waHref = $wa ? 'https://wa.me/'.$wa : '#contacto';

    $nav = [['Especialidades', '#especialidades'], ['Médicos', '#medicos'], ['Cómo reservar', '#reservar'], ['Contacto', '#contacto']];

    $especialidades = [
        ['Pediatría', 'baby', 'Control y cuidado de niños y adolescentes.'],
        ['Cardiología', 'heart-pulse', 'Prevención y tratamiento del corazón.'],
        ['Ginecología', 'flower', 'Salud integral de la mujer.'],
        ['Medicina general', 'stethoscope', 'Consulta, chequeos y orientación.'],
        ['Traumatología', 'bone', 'Huesos, articulaciones y lesiones.'],
        ['Dermatología', 'sparkles', 'Cuidado de la piel, cabello y uñas.'],
    ];

    $medicos = [
        ['Dr. Carlos Mamani', 'Cardiología', 'Lunes, miércoles y viernes', 'dr1.jpg'],
        ['Dra. Valeria Rojas', 'Pediatría', 'Martes, jueves y sábado', 'dr2.jpg'],
        ['Dra. Patricia Vargas', 'Ginecología', 'Lunes a viernes', 'dr3.jpg'],
    ];

    $fotos = [
        ['c1.jpg', 'Pediatra revisando a una niña junto a su madre'],
        ['c2.jpg', 'Médico conversando con un paciente adulto mayor'],
        ['c3.jpg', 'Doctora atendiendo a una paciente embarazada'],
        ['c4.jpg', 'Médico y paciente sonriendo en el pasillo de la clínica'],
    ];

    $pasos = [
        ['user-plus', 'Crea tu cuenta', 'Regístrate con tus datos básicos en menos de un minuto.'],
        ['calendar-check', 'Elige médico y horario', 'Consulta la disponibilidad de cada especialista.'],
        ['check-circle', 'Confirma', 'Recibe la confirmación de tu cita y listo.'],
    ];

    $faqs = [
        ['¿Cómo reservo una cita?', 'Cree su cuenta, elija el médico y el horario disponible, y confirme. También puede llamar a la línea de citas.'],
        ['¿Puedo cancelar o cambiar mi cita?', 'Sí. Desde su cuenta puede reprogramar o cancelar, o comunicarse por teléfono o WhatsApp.'],
        ['¿Qué debo llevar a mi consulta?', 'Su documento de identidad y, si los tiene, estudios o recetas anteriores.'],
        ['¿Atienden emergencias?', 'Para emergencias comuníquese directamente a la línea de emergencias.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $c['nombre'] }} · {{ $c['ciudad'] }}</title>
    <meta name="description" content="Reserve su cita con médicos especialistas en {{ $c['nombre'] }}, {{ $c['ciudad'] }}.">
    <meta property="og:title" content="{{ $c['nombre'] }}">
    <meta property="og:description" content="Su salud, bien organizada. Reserve su cita con especialistas.">
    <meta property="og:type" content="website">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-fondo text-tinta">
        @auth
            <!-- Sesión abierta: se pregunta si continúa o cierra sesión -->
            <div class="bg-deep text-crema" role="region" aria-label="Sesión abierta">
                <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm">Tiene una sesión abierta como <strong>{{ auth()->user()->name }}</strong>.</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-amber !min-h-10">Continuar al panel</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost-light !min-h-10">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        @endauth

    {{-- Aviso superior --}}
    <div class="bg-deep text-crema text-sm">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-1 px-4 py-2">
            <span class="flex items-center gap-1.5"><x-icono nombre="clock" class="h-4 w-4 text-ambar" /> Atención de lunes a sábado · {{ $c['ciudad'] }}</span>
            <span class="flex flex-wrap items-center gap-x-5 gap-y-1">
                <span class="flex items-center gap-1.5"><x-icono nombre="phone" class="h-4 w-4 text-ambar" /> Citas y consultas: <strong class="font-semibold">{{ $c['telefono_citas'] }}</strong></span>
                <a href="{{ route('login') }}" class="text-ambar underline-offset-2 hover:underline">Portal del personal →</a>
            </span>
        </div>
    </div>

    {{-- Menú --}}
    <header x-data="{ abierto: false }" class="sticky top-0 z-40 border-b border-linea bg-fondo/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ url('/') }}" aria-label="{{ $c['nombre'] }}, inicio"><x-logo-aurora /></a>
            <nav class="hidden items-center gap-7 lg:flex" aria-label="Principal">
                @foreach ($nav as [$etiqueta, $ancla])
                    <a href="{{ $ancla }}" class="font-medium hover:text-primary">{{ $etiqueta }}</a>
                @endforeach
            </nav>
            <div class="hidden items-center gap-2 lg:flex">
                <a href="{{ route('login') }}" class="btn btn-outline">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Reservar cita <x-icono nombre="arrow-right" class="h-4 w-4" /></a>
            </div>
            <button type="button" class="btn px-3 lg:hidden" @click="abierto = !abierto" :aria-expanded="abierto" aria-label="Abrir menú">
                <x-icono nombre="menu" class="h-6 w-6" x-show="!abierto" />
                <x-icono nombre="x" class="h-6 w-6" x-show="abierto" x-cloak />
            </button>
        </div>
        <nav x-show="abierto" x-cloak class="border-t border-linea bg-fondo px-4 pb-5 lg:hidden" aria-label="Móvil">
            @foreach ($nav as [$etiqueta, $ancla])
                <a href="{{ $ancla }}" @click="abierto = false" class="block border-b border-linea py-3.5 font-medium">{{ $etiqueta }}</a>
            @endforeach
            <div class="mt-4 grid gap-2">
                <a href="{{ route('register') }}" class="btn btn-primary">Reservar cita</a>
                <a href="{{ route('login') }}" class="btn btn-outline">Iniciar sesión</a>
            </div>
        </nav>
    </header>

    {{-- Portada --}}
    <section class="relative overflow-hidden bg-deep text-crema">
        <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-ambar/10 blur-3xl"></div>
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 md:py-20 lg:grid-cols-2">
            <div>
                <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-crema/10 px-3 py-1 text-sm text-ambar-claro">
                    <x-icono nombre="map-pin" class="h-4 w-4" /> {{ $c['ciudad'] }}
                </p>
                <h1 class="text-5xl font-semibold leading-[1.05] md:text-7xl">
                    Su salud,<br><em class="text-ambar-claro">bien organizada.</em>
                </h1>
                <p class="mt-5 max-w-lg text-lg text-crema/80">
                    Atención médica de primer nivel con calidez humana, tecnología moderna y médicos especialistas comprometidos con su bienestar y el de su familia en Santa Cruz.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="btn btn-amber"><x-icono nombre="calendar-check" class="h-5 w-5" /> Reservar cita</a>
                    <a href="{{ route('login') }}" class="btn btn-ghost-light">Iniciar sesión</a>
                </div>
                <ul class="mt-10 flex flex-wrap gap-x-8 gap-y-3 border-t border-crema/20 pt-6 text-sm text-crema/80">
                    <li class="flex items-center gap-2"><x-icono nombre="user-check" class="h-5 w-5 text-ambar" /> Atención personalizada</li>
                    <li class="flex items-center gap-2"><x-icono nombre="shield-check" class="h-5 w-5 text-ambar" /> Médicos especialistas</li>
                    <li class="flex items-center gap-2"><x-icono nombre="clock" class="h-5 w-5 text-ambar" /> Citas puntuales</li>
                </ul>
            </div>
            <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                <div class="absolute -inset-3 rotate-2 rounded-2xl bg-ambar/30"></div>
                <img src="{{ asset('img/hero-doctora.jpg') }}" alt="Doctora sonriendo con una tablet" width="896" height="1088"
                     class="relative aspect-[4/5] w-full rounded-2xl object-cover">
            </div>
        </div>
    </section>

    {{-- Carrusel automático --}}
    <section aria-label="Fotos de nuestra clínica" class="overflow-hidden border-b border-linea py-8">
        <div class="marquee flex w-max gap-5">
            @foreach ([0, 1] as $copia)
                @foreach ($fotos as [$archivo, $alt])
                    <img src="{{ asset('img/'.$archivo) }}" alt="{{ $copia === 0 ? $alt : '' }}" @if ($copia === 1) aria-hidden="true" @endif
                         loading="lazy" width="944" height="704" class="h-48 w-64 rounded-xl object-cover md:h-60 md:w-80">
                @endforeach
            @endforeach
        </div>
    </section>

    {{-- Especialidades --}}
    <section id="especialidades" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-16 md:py-24">
        <div class="mb-10 text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-primary">Especialidades</p>
            <h2 class="mt-2 text-4xl font-semibold text-deep md:text-5xl">Nuestras especialidades</h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($especialidades as [$nombre, $icono, $detalle])
                <article class="group rounded-xl border border-linea bg-white p-6 transition hover:-translate-y-1 hover:border-primary">
                    <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-ambar-claro text-deep transition group-hover:bg-ambar">
                        <x-icono :nombre="$icono" class="h-6 w-6" />
                    </span>
                    <h3 class="text-xl font-semibold">{{ $nombre }}</h3>
                    <p class="mt-1 text-suave">{{ $detalle }}</p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Médicos --}}
    <section id="medicos" class="scroll-mt-24 bg-white py-16 md:py-24">
        <div class="mx-auto max-w-7xl px-4">
            <div class="mb-10 text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-primary">Equipo</p>
                <h2 class="mt-2 text-4xl font-semibold text-deep md:text-5xl">Nuestros médicos</h2>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($medicos as [$nombre, $especialidad, $dias, $foto])
                    <article class="overflow-hidden rounded-xl border border-linea bg-fondo">
                        <img src="{{ asset('img/'.$foto) }}" alt="Retrato de {{ $nombre }}" loading="lazy" width="752" height="896" class="aspect-square w-full object-cover object-top">
                        <div class="p-5">
                            <p class="text-sm font-semibold uppercase tracking-wider text-primary">{{ $especialidad }}</p>
                            <h3 class="mt-1 text-2xl font-semibold">{{ $nombre }}</h3>
                            <p class="mt-3 flex items-center gap-2 text-suave"><x-icono nombre="clock" class="h-4 w-4" /> {{ $dias }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Cómo reservar --}}
    <section id="reservar" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-16 md:py-24">
        <div class="mb-10 text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-primary">Fácil y rápido</p>
            <h2 class="mt-2 text-4xl font-semibold text-deep md:text-5xl">Cómo reservar tu cita</h2>
        </div>
        <ol class="grid gap-5 md:grid-cols-3">
            @foreach ($pasos as $i => [$icono, $titulo, $detalle])
                <li class="relative rounded-xl border border-linea bg-white p-6">
                    <span class="font-display text-6xl font-semibold text-ambar">{{ $i + 1 }}</span>
                    <x-icono :nombre="$icono" class="absolute right-6 top-6 h-7 w-7 text-primary" />
                    <h3 class="mt-2 text-xl font-semibold">{{ $titulo }}</h3>
                    <p class="mt-1 text-suave">{{ $detalle }}</p>
                </li>
            @endforeach
        </ol>
        <div class="mt-8 text-center"><a href="{{ route('register') }}" class="btn btn-primary">Reservar cita</a></div>
    </section>

    {{-- Contacto --}}
    <section id="contacto" class="scroll-mt-24 bg-deep py-16 text-crema md:py-24">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-ambar">Contacto</p>
                <h2 class="mt-2 text-4xl font-semibold md:text-5xl">Estamos para atenderle</h2>
                <p class="mt-4 max-w-md text-crema/80">Comuníquese con nosotros por teléfono o WhatsApp, o visítenos en la clínica.</p>
            </div>
            <ul class="grid gap-4 sm:grid-cols-2">
                @foreach ([['phone', 'Citas', $c['telefono_citas']], ['message-circle', 'WhatsApp', $c['telefono_whatsapp']], ['siren', 'Emergencias', $c['telefono_emergencias']], ['clock', 'Horario', $c['horario']]] as [$icono, $etiqueta, $valor])
                    <li class="flex h-full gap-4 rounded-xl bg-crema/10 p-5 ring-1 ring-crema/20">
                        <x-icono :nombre="$icono" class="h-6 w-6 shrink-0 text-ambar" />
                        <div><p class="text-sm text-crema/70">{{ $etiqueta }}</p><p class="font-semibold">{{ $valor }}</p></div>
                    </li>
                @endforeach
                <li class="flex gap-4 rounded-xl bg-crema/10 p-5 ring-1 ring-crema/20 sm:col-span-2">
                    <x-icono nombre="map-pin" class="h-6 w-6 shrink-0 text-ambar" />
                    <div><p class="text-sm text-crema/70">Dirección</p><p class="font-semibold">{{ $c['direccion'] }}</p></div>
                </li>
            </ul>
        </div>
    </section>

    {{-- Preguntas frecuentes --}}
    <section class="mx-auto max-w-3xl px-4 py-16 md:py-24">
        <div class="mb-10 text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-primary">Dudas</p>
            <h2 class="mt-2 text-4xl font-semibold text-deep md:text-5xl">Preguntas frecuentes</h2>
        </div>
        <div class="space-y-3">
            @foreach ($faqs as [$pregunta, $respuesta])
                <details class="group rounded-xl border border-linea bg-white px-5">
                    <summary class="flex min-h-[56px] cursor-pointer list-none items-center justify-between gap-4 py-4 font-semibold">
                        {{ $pregunta }}<x-icono nombre="chevron-down" class="h-5 w-5 shrink-0 transition group-open:rotate-180" />
                    </summary>
                    <p class="pb-5 text-suave">{{ $respuesta }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- Pie --}}
    <footer class="bg-deep text-crema">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:grid-cols-3">
            <div>
                <x-logo-aurora :light="true" />
                <p class="mt-4 max-w-xs text-sm text-crema/70">{{ $c['ciudad'] }}, Bolivia.</p>
            </div>
            <div class="space-y-1.5 text-sm">
                <p class="mb-2 font-semibold text-ambar">Teléfonos</p>
                <p>Citas: {{ $c['telefono_citas'] }}</p>
                <p>WhatsApp: {{ $c['telefono_whatsapp'] }}</p>
                <p>Emergencias: {{ $c['telefono_emergencias'] }}</p>
            </div>
            <div>
                <p class="mb-3 text-sm font-semibold text-ambar">Síganos</p>
                <div class="flex gap-3">
                    <a href="#" aria-label="Facebook" class="btn h-12 w-12 bg-crema/10 !p-0 hover:bg-crema/20"><x-icono nombre="facebook" class="h-5 w-5" /></a>
                    <a href="#" aria-label="Instagram" class="btn h-12 w-12 bg-crema/10 !p-0 hover:bg-crema/20"><x-icono nombre="instagram" class="h-5 w-5" /></a>
                </div>
            </div>
        </div>
        <p class="border-t border-crema/20 py-5 text-center text-xs text-crema/60">© {{ now()->year }} {{ $c['nombre'] }}</p>
    </footer>

    {{-- WhatsApp flotante --}}
    <a href="{{ $waHref }}" @if ($wa) target="_blank" rel="noopener" @endif
       class="fixed bottom-5 right-5 z-50 flex items-center gap-3 rounded-full border-2 border-crema/80 bg-primary py-2 pl-2 pr-5 font-semibold text-white shadow-lg transition hover:scale-105">
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-ambar text-tinta"><x-icono nombre="message-circle" class="h-5 w-5" /></span>
        <span class="hidden sm:inline">WhatsApp Santa Aurora</span>
        <span class="sm:hidden sr-only">WhatsApp Santa Aurora</span>
    </a>
</body>
</html>
