{{-- Menú lateral: escritorio (fijo, se puede colapsar) y celular (pantalla completa).
     Las opciones dependen del rol (App\Support\Menu::agrupado). Debe estar dentro de x-data="panelLateral". --}}
@php
    $grupos = \App\Support\Menu::agrupado(Auth::user());
@endphp

<!-- Menú a pantalla completa (celular y tablet) -->
<div x-show="abierto" x-cloak class="lg:hidden fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Menú principal" @keydown.escape.window="abierto = false">
    <div class="absolute inset-0 bg-tinta/50" @click="abierto = false"></div>
    <aside class="relative flex h-full w-72 max-w-[85vw] flex-col gap-4 border-r border-linea bg-tarjeta p-4 shadow-xl">
        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard') }}"><x-logo-aurora /></a>
            <button type="button" @click="abierto = false" aria-label="Cerrar menú" class="flex h-11 w-11 items-center justify-center rounded-xl text-suave hover:bg-tenue">
                <x-icono nombre="x" class="h-6 w-6" />
            </button>
        </div>
        @include('layouts.menu-items', ['grupos' => $grupos, 'movil' => true])
        @include('layouts.menu-tema', ['movil' => true])
    </aside>
</div>

<!-- Menú lateral (escritorio) -->
<aside class="hidden lg:flex lg:sticky lg:top-0 lg:h-screen lg:shrink-0 flex-col gap-4 border-r border-linea bg-tarjeta p-3 transition-[width] duration-200"
       :class="colapsado ? 'lg:w-[76px]' : 'lg:w-72'">
    <div class="flex h-14 items-center px-1" :class="colapsado && 'justify-center'">
        <a href="{{ route('dashboard') }}" aria-label="Clínica Santa Aurora, ir al inicio">
            <span x-show="!colapsado"><x-logo-aurora /></span>
            <span x-show="colapsado" x-cloak><x-logo-aurora :texto="false" /></span>
        </a>
    </div>
    @include('layouts.menu-items', ['grupos' => $grupos, 'movil' => false])
    @include('layouts.menu-tema', ['movil' => false])
</aside>
