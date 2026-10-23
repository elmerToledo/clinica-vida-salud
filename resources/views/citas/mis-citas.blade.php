<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>Mis <em>citas</em></h2>
            <x-boton :href="route('citas.disponibilidad')">Reservar cita</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @foreach ([['Próximas citas', $proximas, 'No tiene citas próximas. Puede reservar una cuando quiera.'], ['Historial', $historial, 'Aún no tiene citas anteriores.']] as [$titulo, $lista, $vacio])
                <section class="space-y-2">
                    <h3 class="font-display text-xl font-semibold text-gray-900">{{ $titulo }}</h3>
                    <div class="card-soft overflow-hidden">
                        @forelse ($lista as $cita)
                            <a href="{{ route('citas.show', $cita) }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-5 py-4 border-b border-linea last:border-b-0 hover:bg-ambar-claro/20 transition {{ $cita->estado === 'cancelada' ? 'opacity-60' : '' }}">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $cita->fecha->format('d/m/Y') }} · {{ $cita->inicio }}</div>
                                    <div class="text-sm text-gray-600">{{ $cita->medico->nombres }} {{ $cita->medico->apellidos }} · {{ $cita->especialidad->nombre }}</div>
                                </div>
                                <x-cita-estado :estado="$cita->estado" />
                            </a>
                        @empty
                            <div class="p-6 text-center text-gray-500">{{ $vacio }}</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
