{{-- Opciones del menú lateral con grupos desplegables (App\Support\Menu::agrupado).
     $movil = true cuando se dibuja dentro del menú a pantalla completa del celular (nunca colapsado). --}}
@php
    $col = ($movil ?? false) ? 'false' : 'colapsado';
    $esActivo = fn (array $e) => request()->routeIs($e['patron']) && ! ($e['excluir'] && request()->routeIs(...$e['excluir']));
    $base = 'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary';
    $inactivo = 'text-suave hover:bg-tenue hover:text-tinta';
    $activoCls = 'bg-primary/10 font-semibold text-primary';
@endphp

<nav class="flex flex-1 flex-col gap-1 overflow-y-auto" aria-label="Menú principal">
    @foreach ($grupos as $g)
        @if (isset($g['items']))
            @php
                $abierto = collect($g['items'])->contains($esActivo);
                $primero = $g['items'][0];
                $id = 'grupo-' . $loop->index . (($movil ?? false) ? '-m' : '');
            @endphp
            {{-- Grupo desplegable --}}
            <div x-data="{ abierto: @js($abierto) }">
                {{-- Barra colapsada (escritorio): solo el ícono, lleva a la primera opción --}}
                <a href="{{ route($primero['ruta']) }}" x-show="{{ $col }}" x-cloak title="{{ $g['etiqueta'] }}"
                   class="{{ $base }} justify-center px-0 {{ $abierto ? $activoCls : $inactivo }}">
                    <x-icono :nombre="$g['icono']" class="h-5 w-5 shrink-0" />
                    <span class="sr-only">{{ $g['etiqueta'] }}</span>
                </a>
                {{-- Barra normal --}}
                <div x-show="!{{ $col }}">
                    <button type="button" @click="abierto = !abierto" :aria-expanded="abierto.toString()" aria-controls="{{ $id }}"
                            class="{{ $base }} {{ $abierto ? 'text-primary' : $inactivo }}">
                        <x-icono :nombre="$g['icono']" class="h-5 w-5 shrink-0" />
                        <span class="flex-1 text-left">{{ $g['etiqueta'] }}</span>
                        <x-icono nombre="chevron-down" class="h-4 w-4 shrink-0 transition-transform duration-200" ::class="abierto && 'rotate-180'" />
                    </button>
                    <div id="{{ $id }}" class="grid transition-all duration-200 ease-out"
                         :class="abierto ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'">
                        <ul class="ml-5 min-h-0 overflow-hidden border-l border-linea pl-3">
                            @foreach ($g['items'] as $item)
                                @php($a = $esActivo($item))
                                <li>
                                    <a href="{{ route($item['ruta']) }}" @if ($a) aria-current="page" @endif :tabindex="abierto ? 0 : -1"
                                       class="my-0.5 block rounded-lg px-3 py-2 text-sm {{ $a ? $activoCls : $inactivo }}">{{ $item['etiqueta'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @else
            @php($a = $esActivo($g))
            <a href="{{ route($g['ruta']) }}" @if ($a) aria-current="page" @endif title="{{ $g['etiqueta'] }}"
               :class="{{ $col }} && 'justify-center px-0'"
               class="{{ $base }} {{ $a ? 'bg-primary text-sobre-primary font-semibold' : $inactivo }}">
                <x-icono :nombre="$g['icono']" class="h-5 w-5 shrink-0" />
                <span x-show="!{{ $col }}">{{ $g['etiqueta'] }}</span>
            </a>
        @endif
    @endforeach
</nav>
