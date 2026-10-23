{{-- Menú lateral (escritorio) y barra superior con menú a pantalla completa (celular y tablet).
     Las opciones dependen del rol (App\Support\Menu). --}}
@php
    $menu = \App\Support\Menu::para(Auth::user());
    $iconos = ['dashboard' => 'home', 'usuarios.index' => 'lock', 'pacientes.index' => 'users', 'medicos.index' => 'stethoscope', 'especialidades.index' => 'heart', 'horarios.index' => 'clock', 'bitacora.index' => 'file-text', 'citas.index' => 'calendar-check', 'citas.disponibilidad' => 'calendar-check', 'mis-citas.index' => 'file-text', 'perfil.edit' => 'user-check'];
    $inicial = mb_strtoupper(mb_substr(Auth::user()->name, 0, 1));
@endphp

<div x-data="{ abierto: false }" class="lg:contents">
    <!-- Barra superior (celular y tablet) -->
    <header class="lg:hidden flex items-center justify-between bg-deep px-4 py-2 text-white">
        <a href="{{ route('dashboard') }}"><x-logo-aurora :light="true" class="[&_svg]:h-8 [&_svg]:w-8" /></a>
        <button type="button" @click="abierto = true" aria-label="Abrir menú" class="flex h-12 w-12 items-center justify-center rounded-lg hover:bg-white/10">
            <x-icono nombre="menu" class="h-7 w-7" />
        </button>
    </header>

    <!-- Menú a pantalla completa (celular y tablet) -->
    <div x-show="abierto" x-cloak class="lg:hidden fixed inset-0 z-50 flex flex-col overflow-y-auto bg-deep p-4 pb-6 text-white" role="dialog" aria-label="Menú principal">
        <div class="flex items-center justify-between pb-2">
            <span class="font-display text-lg">Menú</span>
            <button type="button" @click="abierto = false" aria-label="Cerrar menú" class="flex h-12 w-12 items-center justify-center rounded-lg hover:bg-white/10">
                <x-icono nombre="x" class="h-7 w-7" />
            </button>
        </div>
        <nav class="flex flex-col gap-1" aria-label="Menú principal">
            @foreach ($menu as $item)
                @php($activo = request()->routeIs($item['patron']))
                <a href="{{ route($item['ruta']) }}" @if ($activo) aria-current="page" @endif
                   class="flex min-h-[52px] items-center gap-3 rounded-xl px-3.5 text-[17px] {{ $activo ? 'bg-ambar-claro font-bold text-deep' : 'font-medium text-[#e1eeef] hover:bg-white/10' }}">
                    <x-icono :nombre="$iconos[$item['ruta']] ?? 'home'" class="h-5 w-5" /> {{ $item['etiqueta'] }}
                </a>
            @endforeach
        </nav>
        <div class="mt-auto flex items-center justify-between gap-3 border-t border-white/15 pt-4">
            <div class="min-w-0">
                <div class="truncate text-[15px] font-semibold">{{ Auth::user()->name }}</div>
                <a href="{{ route('profile.edit') }}" class="text-sm text-[#bbd6d9] underline">Mi cuenta</a>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex min-h-[44px] items-center rounded-lg border border-white/40 px-4 text-sm font-semibold hover:bg-white/10">Cerrar sesión</button>
            </form>
        </div>
    </div>

    <!-- Menú lateral (escritorio) -->
    <aside class="hidden lg:flex lg:sticky lg:top-0 lg:h-screen lg:w-72 lg:shrink-0 flex-col gap-7 bg-deep px-4 py-7 text-white">
        <a href="{{ route('dashboard') }}" class="px-2"><x-logo-aurora :light="true" /></a>

        <nav class="flex flex-col gap-1" aria-label="Menú principal">
            @foreach ($menu as $item)
                @php($activo = request()->routeIs($item['patron']))
                <a href="{{ route($item['ruta']) }}" @if ($activo) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-2xl px-4 py-3 text-[15px] {{ $activo ? 'bg-ambar-claro font-bold text-deep' : 'font-medium text-[#e1eeef] hover:bg-white/10' }}">
                    <x-icono :nombre="$iconos[$item['ruta']] ?? 'home'" class="h-5 w-5" /> {{ $item['etiqueta'] }}
                </a>
            @endforeach
        </nav>

        <div class="mt-auto border-t border-white/15 pt-4">
            <div class="flex items-center gap-3 px-2">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-ambar font-bold text-tinta">{{ $inicial }}</div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-[#bbd6d9]">{{ Auth::user()->rol_nombre }}</div>
                </div>
            </div>
            <div class="mt-3 flex flex-col gap-1 text-sm">
                <a href="{{ route('profile.edit') }}" class="rounded-lg px-2 py-2 text-[#e1eeef] hover:bg-white/10">Mi cuenta</a>
                <!-- CU02: cerrar sesión -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-2 py-2 text-left text-[#e1eeef] hover:bg-white/10">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </aside>
</div>
