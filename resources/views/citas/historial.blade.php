<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>Historial de <em>citas</em></h2>
            <x-boton tipo="secundario" :href="route('pacientes.index')">Volver a pacientes</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="card-soft p-5 text-sm">
                <div class="font-semibold text-gray-900">{{ $paciente->nombres }} {{ $paciente->apellidos }}</div>
                <div class="text-gray-600">{{ $paciente->codigo }} · CI {{ $paciente->ci }} · {{ $paciente->activo ? 'Activo' : 'Inactivo' }}</div>
            </div>

            <div class="card-soft overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
                            <tr>
                                <th class="px-4 py-3">Fecha y hora</th>
                                <th class="px-4 py-3">Código</th>
                                <th class="px-4 py-3">Médico</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Especialidad</th>
                                <th class="px-4 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-linea">
                            @forelse ($citas as $cita)
                                <tr class="{{ $cita->estado === 'cancelada' ? 'opacity-60' : '' }}">
                                    <td class="px-4 py-3 whitespace-nowrap font-medium">{{ $cita->fecha->format('d/m/Y') }} · {{ $cita->inicio }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap"><a href="{{ route('citas.show', $cita) }}" class="accion">{{ $cita->codigo }}</a></td>
                                    <td class="px-4 py-3">{{ $cita->medico->apellidos }}, {{ $cita->medico->nombres }}</td>
                                    <td class="px-4 py-3 hidden sm:table-cell">{{ $cita->especialidad->nombre }}</td>
                                    <td class="px-4 py-3"><x-cita-estado :estado="$cita->estado" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Este paciente todavía no tiene citas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $citas->links() }}</div>
        </div>
    </div>
</x-app-layout>
