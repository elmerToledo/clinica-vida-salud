<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Recordatorios de citas</h2>
                <p>Citas de mañana ({{ \Carbon\CarbonImmutable::parse($fecha)->format('d/m/Y') }}). Se avisa al paciente por la campanita de su cuenta y por correo.</p>
            </div>
            <form method="POST" action="{{ route('recordatorios.enviar') }}">
                @csrf
                <x-boton>Enviar recordatorios</x-boton>
            </form>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-tabla>
                <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
                    <tr>
                        <th class="px-5 py-4">Hora</th>
                        <th class="px-5 py-4">Paciente</th>
                        <th class="px-5 py-4">Médico</th>
                        <th class="px-5 py-4">Medio</th>
                        <th class="px-5 py-4">Recordatorio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($citas as $cita)
                        @php($medio = $servicio->medio($cita))
                        <tr>
                            <td class="px-5 py-4 font-semibold text-tinta">{{ $cita->inicio }}</td>
                            <td class="px-5 py-4">{{ $cita->paciente->nombre_completo }}</td>
                            <td class="px-5 py-4">{{ $cita->medico->apellidos }}</td>
                            <td class="px-5 py-4">{{ match ($medio) { 'portal' => 'Campanita y correo', 'correo' => 'Correo', default => 'Sin medio de contacto' } }}</td>
                            <td class="px-5 py-4">{{ $cita->recordatorio_enviado_at ? 'Enviado ' . $cita->recordatorio_enviado_at->timezone(\App\Services\CitaService::ZONA)->format('d/m H:i') : 'Pendiente' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-suave">No hay citas vigentes para mañana.</td></tr>
                    @endforelse
                </tbody>
            </x-tabla>
        </div>
    </div>
</x-app-layout>
