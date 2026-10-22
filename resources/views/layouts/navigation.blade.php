{{-- Barra de navegación: las opciones dependen del rol (App\Support\Menu). Responsive: menú de hamburguesa bajo 1024 px. --}}
@php($menu = \App\Support\Menu::para(Auth::user()))

<nav x-data="{ open: false }" class="bg-white border-b border-gray-200">
    <!-- Menú principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Marca -->
                <a href="{{ route('dashboard') }}" class="shrink-0 flex items-center gap-2 font-semibold text-teal-700">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 3h4v7h7v4h-7v7h-4v-7H3v-4h7z"/></svg>
                    <span>Vida Salud</span>
                </a>

                <!-- Opciones (escritorio) -->
                <div class="hidden lg:flex lg:space-x-6 lg:ms-8">
                    @foreach ($menu as $item)
                        <x-nav-link :href="route($item['ruta'])" :active="request()->routeIs($item['patron'])">
                            {{ $item['etiqueta'] }}
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <!-- Menú del usuario (escritorio) -->
            <div class="hidden lg:flex lg:items-center lg:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="text-start">
                                <div class="text-gray-800">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-teal-700">{{ Auth::user()->rol_nombre }}</div>
                            </div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Mi cuenta
                        </x-dropdown-link>

                        <!-- CU02: cerrar sesión -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                Cerrar sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Botón de hamburguesa (celular y tablet) -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" aria-label="Abrir menú">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menú desplegable (celular y tablet) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @foreach ($menu as $item)
                <x-responsive-nav-link :href="route($item['ruta'])" :active="request()->routeIs($item['patron'])">
                    {{ $item['etiqueta'] }}
                </x-responsive-nav-link>
            @endforeach
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                <div class="text-xs text-teal-700">{{ Auth::user()->rol_nombre }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Mi cuenta
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        Cerrar sesión
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
