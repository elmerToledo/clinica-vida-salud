{{-- Panel lateral del asistente: datos rápidos y consejos. --}}
@php
    $u = auth()->user();
    $hoy = app(\App\Services\CitaService::class)->hoy();

    if ($u->tieneRol('paciente') && $u->paciente) {
        $propias = \App\Models\Cita::where('paciente_id', $u->paciente->id)->where('fecha', '>=', $hoy);
        $datos = [
            ['Mis próximas citas', (clone $propias)->ocupan()->count()],
            ['Por confirmar', (clone $propias)->where('estado', 'programada')->count()],
        ];
    } else {
        $datos = [
            ['Citas de hoy', \App\Models\Cita::where('fecha', $hoy)->ocupan()->count()],
            ['Pendientes de confirmar', \App\Models\Cita::where('fecha', '>=', $hoy)->where('estado', 'programada')->count()],
        ];
    }
@endphp

<div class="space-y-4">
    <aside class="card-soft overflow-hidden" aria-label="Información rápida">
        <h3 class="border-b border-linea px-5 py-4 font-bold text-tinta">Información rápida</h3>
        <div class="space-y-3 p-5">
            <p class="text-sm text-tinta"><span class="font-bold">Cancelación:</span> la cita se puede cancelar o reprogramar mientras esté programada o confirmada.</p>
            @foreach ($datos as [$etiqueta, $valor])
                <div class="flex items-center justify-between rounded-xl bg-tenue px-4 py-3 text-sm">
                    <span class="text-suave">{{ $etiqueta }}</span>
                    <span class="font-bold text-tinta">{{ $valor }}</span>
                </div>
            @endforeach
        </div>
    </aside>

    <aside class="card-soft p-5">
        <h3 class="font-bold text-tinta">Consejos</h3>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-suave marker:text-primary">
            @if ($u->tieneRol('recepcionista'))
                <li>Verifique la CI del paciente antes de agendar.</li>
                <li>Las franjas tachadas ya están ocupadas.</li>
                <li>Si el paciente es nuevo, regístrelo primero.</li>
            @else
                <li>Llegue 10 minutos antes de la hora de su cita.</li>
                <li>Traiga su cédula de identidad y estudios previos, si los tiene.</li>
                <li>Las franjas tachadas ya están ocupadas.</li>
            @endif
        </ul>
    </aside>
</div>
