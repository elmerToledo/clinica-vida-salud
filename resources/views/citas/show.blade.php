@php($usuario = auth()->user())

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>Cita <em>{{ $cita->codigo }}</em></h2>
            @if ($usuario->tieneRol('paciente'))
                <x-boton tipo="secundario" :href="route('dashboard')">Volver</x-boton>
            @else
                <x-boton tipo="secundario" :href="route('citas.index', ['desde' => $cita->fecha->toDateString(), 'hasta' => $cita->fecha->toDateString()])">Volver a la agenda</x-boton>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $mensaje)
                            <li>{{ $mensaje }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card-soft overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 bg-ambar-claro/40 border-b border-linea">
                    <div class="font-semibold text-gray-900">{{ $cita->fecha->format('d/m/Y') }} · {{ $cita->inicio }} a {{ $cita->fin }}</div>
                    <x-cita-estado :estado="$cita->estado" />
                </div>
                <dl class="grid gap-x-6 gap-y-4 p-5 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-deep">Paciente</dt>
                        <dd class="mt-1">{{ $cita->paciente->nombres }} {{ $cita->paciente->apellidos }}<br><span class="text-gray-500">CI {{ $cita->paciente->ci }} · {{ $cita->paciente->codigo }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-deep">Médico</dt>
                        <dd class="mt-1">{{ $cita->medico->nombres }} {{ $cita->medico->apellidos }}<br><span class="text-gray-500">{{ $cita->especialidad->nombre }}</span></dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-bold uppercase tracking-wider text-deep">Motivo de la consulta</dt>
                        <dd class="mt-1">{{ $cita->motivo_consulta ?: 'No indicado' }}</dd>
                    </div>
                    @if ($cita->llegada_at)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-deep">Llegada del paciente</dt>
                            <dd class="mt-1">{{ $cita->llegada_at->timezone('America/La_Paz')->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($cita->estado === 'cancelada')
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-bold uppercase tracking-wider text-deep">Motivo de la cancelación</dt>
                            <dd class="mt-1">{{ $cita->motivo_cancelacion }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-deep">Registrada por</dt>
                        <dd class="mt-1">{{ $cita->creador?->name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Acciones de la recepcionista (CU26, CU27, CU28, CU29) --}}
            @if ($puedeGestionar)
                <div class="card-soft p-5 space-y-4">
                    <h3 class="font-semibold text-gray-900">Acciones</h3>
                    <div class="flex flex-wrap gap-3">
                        <x-boton tipo="secundario" :href="route('citas.reprogramar', $cita)">Reprogramar</x-boton>

                        @if ($cita->estado === 'programada')
                            <form method="POST" action="{{ route('citas.confirmar', $cita) }}">
                                @csrf
                                @method('PATCH')
                                <x-boton>Confirmar cita</x-boton>
                            </form>
                        @endif

                        @if ($yaLlego && ! $cita->llegada_at)
                            <form method="POST" action="{{ route('citas.asistencia', $cita) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="tipo" value="asistio">
                                <x-boton tipo="secundario">Registrar asistencia</x-boton>
                            </form>
                            <form method="POST" action="{{ route('citas.asistencia', $cita) }}"
                                  onsubmit="return confirm('¿Registrar que el paciente no se presentó?');">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="tipo" value="inasistencia">
                                <x-boton tipo="secundario">Registrar inasistencia</x-boton>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Cancelar: recepcionista o paciente dueño (CU27) --}}
            @if ($puedeGestionar || $puedeCancelarPropia)
                <form method="POST" action="{{ route('citas.cancelar', $cita) }}" class="card-soft p-5 space-y-3"
                      onsubmit="return confirm('¿Cancelar esta cita? El horario quedará libre.');">
                    @csrf
                    @method('PATCH')
                    <h3 class="font-semibold text-gray-900">Cancelar cita</h3>
                    <div>
                        <label for="motivo_cancelacion" class="block font-semibold text-sm text-tinta">Motivo de la cancelación <span class="text-red-600">*</span></label>
                        <textarea id="motivo_cancelacion" name="motivo_cancelacion" rows="2" maxlength="300" required class="mt-1 campo">{{ old('motivo_cancelacion') }}</textarea>
                    </div>
                    <x-boton tipo="peligro">Cancelar cita</x-boton>
                </form>
            @endif

            {{-- Marcar como atendida: médico con el permiso asignado (CU30) --}}
            @if ($puedeAtender)
                <form method="POST" action="{{ route('citas.atender', $cita) }}" class="card-soft p-5"
                      onsubmit="return confirm('¿Marcar esta cita como atendida?');">
                    @csrf
                    @method('PATCH')
                    <x-boton>Marcar como atendida</x-boton>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
