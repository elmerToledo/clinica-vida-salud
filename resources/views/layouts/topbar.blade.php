{{-- Barra superior: menú (celular), buscador de pacientes, accesos rápidos y menú del usuario. --}}
@php
    $u = Auth::user();
    $inicial = mb_strtoupper(mb_substr($u->name, 0, 1));
    $verBuscador = $u->tieneRol('administrador', 'recepcionista');
@endphp
<div class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-linea bg-tarjeta/90 px-4 backdrop-blur md:px-6">
    <button type="button" @click="abierto = true" aria-label="Abrir menú" class="flex h-11 w-11 items-center justify-center rounded-xl text-tinta hover:bg-tenue lg:hidden">
        <x-icono nombre="menu" class="h-6 w-6" />
    </button>

    @if ($verBuscador)
        <form method="GET" action="{{ route('pacientes.index') }}" role="search" class="relative hidden max-w-md flex-1 sm:block">
            <label for="buscar-global" class="sr-only">Buscar pacientes por nombre o CI</label>
            <x-icono nombre="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-suave" />
            <input id="buscar-global" type="search" name="q" placeholder="Buscar pacientes por nombre o CI…"
                   class="h-10 w-full rounded-xl border border-linea bg-fondo pl-9 pr-3 text-sm text-tinta placeholder:text-suave focus:border-primary focus:ring-2 focus:ring-primary/25">
        </form>
    @endif

    <div class="ml-auto flex items-center gap-2">
        @if ($verBuscador)
            <a href="{{ route('pacientes.create') }}" class="hidden h-10 items-center gap-2 rounded-xl border border-linea bg-tarjeta px-4 text-sm font-semibold text-tinta hover:bg-tenue md:inline-flex">
                <x-icono nombre="user-plus" class="h-4 w-4" /> Nuevo paciente
            </a>
        @endif
        @if ($u->tieneRol('recepcionista', 'paciente'))
            <a href="{{ route('citas.disponibilidad') }}" class="hidden h-10 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-sobre-primary hover:opacity-90 sm:inline-flex">
                <x-icono nombre="calendar-plus" class="h-4 w-4" /> {{ $u->tieneRol('paciente') ? 'Reservar cita' : 'Agendar cita' }}
            </a>
        @endif

        <!-- Menú del usuario -->
        <div class="relative" x-data="{ usuario: false }" @click.outside="usuario = false" @keydown.escape="usuario = false">
            <button type="button" @click="usuario = !usuario" :aria-expanded="usuario.toString()" aria-haspopup="menu" aria-label="Menú del usuario"
                    class="flex items-center gap-2 rounded-full p-0.5 hover:bg-tenue focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/15 text-sm font-bold text-primary" aria-hidden="true">{{ $inicial }}</span>
                <x-icono nombre="chevron-down" class="mr-1 hidden h-4 w-4 text-suave sm:block" />
            </button>
            <div x-show="usuario" x-cloak x-transition.opacity role="menu"
                 class="absolute right-0 mt-2 w-60 rounded-2xl border border-linea bg-tarjeta p-2 text-sm shadow-xl">
                <div class="border-b border-linea px-3 pb-2 pt-1">
                    <div class="truncate font-semibold text-tinta">{{ $u->name }}</div>
                    <div class="text-xs text-suave">{{ $u->rol_nombre }}</div>
                </div>
                <a href="{{ route('profile.edit') }}" role="menuitem" class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2 text-tinta hover:bg-tenue">Mi cuenta</a>
                <!-- CU02: cerrar sesión -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" role="menuitem" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-[rgb(var(--c-peligro))] hover:bg-tenue">
                        <x-icono nombre="log-out" class="h-4 w-4" /> Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
