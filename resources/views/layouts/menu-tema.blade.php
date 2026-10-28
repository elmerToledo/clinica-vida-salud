{{-- Pie del menú lateral: interruptor de modo oscuro y (en escritorio) botón para colapsar. --}}
@php($col = ($movil ?? false) ? 'false' : 'colapsado')
<div class="space-y-1 border-t border-linea pt-3">
    <button type="button" role="switch" :aria-checked="$store.tema.oscuro.toString()" @click="$store.tema.alternar()" title="Modo oscuro"
            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] font-medium text-suave hover:bg-tenue hover:text-tinta focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            :class="{{ $col }} && 'justify-center px-0'">
        <span class="shrink-0"><x-icono nombre="moon" class="h-5 w-5" x-show="$store.tema.oscuro" /><x-icono nombre="sun" class="h-5 w-5" x-show="!$store.tema.oscuro" /></span>
        <span x-show="!{{ $col }}" class="flex-1 text-left">Modo oscuro</span>
        <span x-show="!{{ $col }}" class="relative h-5 w-9 shrink-0 rounded-full transition-colors" :class="$store.tema.oscuro ? 'bg-primary' : 'bg-linea'" aria-hidden="true">
            <span class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-all" :class="$store.tema.oscuro ? 'left-[18px]' : 'left-0.5'"></span>
        </span>
    </button>
    <div class="flex items-center gap-3 rounded-xl px-2 py-2" :class="{{ $col }} && 'justify-center'">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/15 text-sm font-bold text-primary" aria-hidden="true">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
        <div class="min-w-0 flex-1" x-show="!{{ $col }}">
            <div class="truncate text-sm font-semibold text-tinta">{{ Auth::user()->name }}</div>
            <div class="text-xs text-suave">{{ Auth::user()->rol_nombre }}</div>
        </div>
    </div>
    <!-- CU02: cerrar sesión -->
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" title="Cerrar sesión"
                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] font-medium text-[rgb(var(--c-peligro))] hover:bg-tenue focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                :class="{{ $col }} && 'justify-center px-0'">
            <x-icono nombre="log-out" class="h-5 w-5 shrink-0" />
            <span x-show="!{{ $col }}">Cerrar sesión</span>
        </button>
    </form>
    @unless ($movil ?? false)
        <button type="button" @click="alternarColapso()" :aria-label="colapsado ? 'Expandir menú lateral' : 'Colapsar menú lateral'"
                class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-xs text-suave hover:bg-tenue focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                :class="colapsado && 'justify-center px-0'">
            <x-icono nombre="chevrons-left" class="h-4 w-4 transition-transform" ::class="colapsado && 'rotate-180'" />
            <span x-show="!colapsado">Colapsar menú</span>
        </button>
    @endunless
</div>
