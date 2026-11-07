<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Feriados y bloqueos</h2>
                <p>Días en que la clínica o un médico no atiende. En esas fechas no se pueden reservar citas.</p>
            </div>
            <x-boton :href="route('bloqueos.create')">Nuevo bloqueo</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <x-tabla>
                <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
                    <tr>
                        <th class="px-5 py-4">Período</th>
                        <th class="px-5 py-4">Motivo</th>
                        <th class="px-5 py-4">Alcance</th>
                        <th class="px-5 py-4">Estado</th>
                        <th class="px-5 py-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($bloqueos as $b)
                        <tr>
                            <td class="px-5 py-4 font-semibold text-tinta">{{ $b->periodo }}</td>
                            <td class="px-5 py-4">{{ $b->motivo }}</td>
                            <td class="px-5 py-4">{{ $b->alcance }}</td>
                            <td class="px-5 py-4">
                                @if (! $b->activo) Desactivado
                                @elseif ($b->fecha_fin->toDateString() < $hoy) Vencido
                                @else Activo @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <form method="POST" action="{{ route('bloqueos.estado', $b) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-sm font-semibold text-primary hover:underline">{{ $b->activo ? 'Desactivar' : 'Reactivar' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-suave">Aún no hay feriados ni bloqueos registrados.</td></tr>
                    @endforelse
                </tbody>
            </x-tabla>
        </div>
    </div>
</x-app-layout>
