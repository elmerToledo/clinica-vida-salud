<x-app-layout>
    <x-slot name="header">
        <h2>Nueva cita</h2>
        <p>Complete los tres pasos para agendar la cita.</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 lg:grid lg:grid-cols-[11rem_minmax(0,1fr)_19rem] gap-6 space-y-5 lg:space-y-0 items-start">
            <x-pasos-cita :paso="1" />

            <section class="card-soft overflow-hidden">
                <h3 class="border-b border-linea px-5 py-4 font-bold text-tinta">Paso 1: Paciente</h3>
                <div class="p-5 space-y-4">
                    <form method="GET" class="relative">
                        <label for="q" class="sr-only">Buscar paciente</label>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-suave"><x-icono nombre="search" class="h-4 w-4" /></span>
                        <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Buscar por nombre o CI…" maxlength="100" class="campo pl-10">
                    </form>

                    <p class="text-xs font-bold uppercase tracking-wider text-suave">{{ $q === '' ? 'Pacientes recientes' : 'Resultados' }}</p>

                    <ul class="space-y-3">
                        @forelse ($pacientes as $paciente)
                            <li class="flex items-center gap-3 rounded-xl border border-linea p-3">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-primary/15 text-sm font-bold text-primary">{{ mb_strtoupper(mb_substr($paciente->nombres, 0, 1).mb_substr($paciente->apellidos, 0, 1)) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-tinta">{{ $paciente->nombres }} {{ $paciente->apellidos }}</p>
                                    <p class="truncate text-sm text-suave">CI {{ $paciente->ci }} · {{ $paciente->codigo }}@if ($paciente->telefono) · {{ $paciente->telefono }}@endif</p>
                                </div>
                                <a href="{{ route('citas.disponibilidad', ['paciente' => $paciente->id]) }}" class="inline-flex h-9 items-center rounded-lg border border-linea px-3 text-sm font-semibold text-tinta hover:bg-tenue">Seleccionar</a>
                            </li>
                        @empty
                            <li class="rounded-xl border border-dashed border-linea p-6 text-center text-sm text-suave">
                                No se encontraron pacientes activos.
                                <a href="{{ route('pacientes.create') }}" class="accion">Registrar paciente</a>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </section>

            <x-info-cita />
        </div>
    </div>
</x-app-layout>
