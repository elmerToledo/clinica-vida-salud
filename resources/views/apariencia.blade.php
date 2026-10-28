<x-app-layout>
    <x-slot name="header">
        <h2>Apariencia</h2>
        <p>Elija el tema y el color del sistema. Se guarda en este navegador.</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-5 lg:grid-cols-2 items-start">
            <div class="space-y-5">
                <section class="card-soft p-5" aria-labelledby="t-tema">
                    <h3 id="t-tema" class="font-bold text-tinta">Tema</h3>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <button type="button" @click="$store.tema.oscuro && $store.tema.alternar()" x-data
                            :class="!$store.tema.oscuro ? 'border-primary ring-2 ring-primary/30' : 'border-linea'"
                            class="flex items-center gap-3 rounded-xl border p-4 text-left text-tinta">
                            <x-icono nombre="sun" class="h-5 w-5" /> <span class="font-semibold">Claro</span>
                        </button>
                        <button type="button" @click="!$store.tema.oscuro && $store.tema.alternar()" x-data
                            :class="$store.tema.oscuro ? 'border-primary ring-2 ring-primary/30' : 'border-linea'"
                            class="flex items-center gap-3 rounded-xl border p-4 text-left text-tinta">
                            <x-icono nombre="moon" class="h-5 w-5" /> <span class="font-semibold">Oscuro</span>
                        </button>
                    </div>
                </section>

                <section class="card-soft p-5" aria-labelledby="t-color">
                    <h3 id="t-color" class="font-bold text-tinta">Color de acento</h3>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        @foreach ([['verde-azulado', 'Verde azulado', '#0f766e'], ['azul', 'Azul', '#2563eb'], ['verde', 'Verde', '#16a34a'], ['vino', 'Vino', '#9f1239']] as [$clave, $nombre, $color])
                            <button type="button" @click="$store.acento.elegir('{{ $clave }}')" x-data
                                :class="$store.acento.actual === '{{ $clave }}' ? 'border-primary ring-2 ring-primary/30' : 'border-linea'"
                                class="flex items-center gap-3 rounded-xl border p-4 text-left text-tinta">
                                <span class="h-6 w-6 rounded-full" style="background: {{ $color }}"></span>
                                <span class="font-semibold">{{ $nombre }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            </div>

            <section class="card-soft p-5" aria-labelledby="t-prev">
                <h3 id="t-prev" class="font-bold text-tinta">Vista previa</h3>
                <div class="mt-3 rounded-xl border border-linea bg-fondo p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="font-semibold text-tinta">Próxima cita</p>
                        <x-cita-estado estado="confirmada" />
                    </div>
                    <p class="text-sm text-suave">Hoy · 09:00 · Consulta general</p>
                    <div class="flex gap-2">
                        <span class="btn-primary inline-flex rounded-xl px-4 py-2 text-sm font-semibold">Agendar cita</span>
                        <span class="btn-outline inline-flex rounded-xl px-4 py-2 text-sm font-semibold">Ver agenda</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-tenue"><div class="h-2 w-2/3 rounded-full bg-primary"></div></div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
