@php
    $fechaObj = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $fecha);
    $fechaTexto = $fechaObj->format('d/m/Y');
    $conocido = $miPaciente ?? $pacienteElegido;
    $volver = route('citas.disponibilidad', array_filter(['medico' => $medico->id, 'fecha' => $fecha, 'paciente' => $pacienteElegido?->id]));
    $unica = $medico->especialidades->count() === 1 ? $medico->especialidades->first()->id : null;
    $porDefecto = old('especialidad_id', $unica ?? ($especialidadElegida ?: null));
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>{{ $miPaciente ? 'Reservar cita' : 'Nueva cita' }}</h2>
        <p>Complete los tres pasos para agendar la cita.</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 lg:grid lg:grid-cols-[11rem_minmax(0,1fr)_19rem] gap-6 space-y-5 lg:space-y-0 items-start">
            <x-pasos-cita :paso="3" :paciente="$conocido?->nombre_completo" />

            <form method="POST" action="{{ route('citas.store') }}" class="card-soft overflow-hidden">
                @csrf
                <input type="hidden" name="medico_id" value="{{ $medico->id }}">
                <input type="hidden" name="fecha" value="{{ $fecha }}">
                <input type="hidden" name="hora_inicio" value="{{ $bloque['inicio'] }}">
                @if ($pacienteElegido)
                    <input type="hidden" name="paciente_id" value="{{ $pacienteElegido->id }}">
                @endif

                <h3 class="border-b border-linea px-5 py-4 font-bold text-tinta">Paso 3: Confirmación</h3>

                <div class="p-5 space-y-5">
                    @if ($errors->any())
                        <div role="alert" class="rounded-md border border-[rgb(var(--c-peligro)/.4)] bg-[rgb(var(--c-peligro)/.1)] p-3 text-sm text-[rgb(var(--c-peligro))]">
                            <ul class="list-disc ml-5">
                                @foreach ($errors->all() as $mensaje)
                                    <li>{{ $mensaje }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <dl class="grid gap-3 sm:grid-cols-2">
                        @if ($conocido)
                            <div class="rounded-xl bg-tenue p-4"><dt class="text-xs text-suave">Paciente</dt><dd class="mt-1 font-bold text-tinta">{{ $conocido->nombres }} {{ $conocido->apellidos }}</dd></div>
                            <div class="rounded-xl bg-tenue p-4"><dt class="text-xs text-suave">CI</dt><dd class="mt-1 font-bold text-tinta">{{ $conocido->ci }}</dd></div>
                        @endif
                        <div class="rounded-xl bg-tenue p-4"><dt class="text-xs text-suave">Médico</dt><dd class="mt-1 font-bold text-tinta">{{ $medico->nombres }} {{ $medico->apellidos }}</dd></div>
                        <div class="rounded-xl bg-tenue p-4"><dt class="text-xs text-suave">Fecha</dt><dd class="mt-1 font-bold text-tinta">{{ $fechaTexto }}</dd></div>
                        <div class="rounded-xl bg-tenue p-4 sm:col-span-2"><dt class="text-xs text-suave">Hora</dt><dd class="mt-1 font-bold text-tinta">{{ $bloque['inicio'] }} a {{ $bloque['fin'] }} ({{ $bloque['duracion'] }} min)</dd></div>
                    </dl>

                    @unless ($conocido)
                        <x-campo-select name="paciente_id" label="Paciente" required>
                            <option value="">Seleccione…</option>
                            @foreach ($pacientes as $paciente)
                                <option value="{{ $paciente->id }}" @selected((string) old('paciente_id') === (string) $paciente->id)>
                                    {{ $paciente->apellidos }}, {{ $paciente->nombres }} · CI {{ $paciente->ci }}
                                </option>
                            @endforeach
                        </x-campo-select>
                    @endunless

                    <x-campo-select name="especialidad_id" label="Especialidad de la consulta" required>
                        <option value="">Seleccione…</option>
                        @foreach ($medico->especialidades as $especialidad)
                            <option value="{{ $especialidad->id }}" @selected((string) $porDefecto === (string) $especialidad->id)>
                                {{ $especialidad->nombre }}
                            </option>
                        @endforeach
                    </x-campo-select>

                    <div>
                        <label for="motivo_consulta" class="block font-semibold text-sm text-tinta">Motivo de la consulta (opcional)</label>
                        <textarea id="motivo_consulta" name="motivo_consulta" rows="3" maxlength="500" class="mt-1 campo">{{ old('motivo_consulta') }}</textarea>
                        <x-input-error :messages="$errors->get('motivo_consulta')" class="mt-1" />
                    </div>

                    <p class="text-xs text-suave"><span class="text-[rgb(var(--c-peligro))]">*</span> Campos obligatorios</p>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-linea px-5 py-4">
                    <x-boton tipo="secundario" :href="$volver">Anterior</x-boton>
                    <x-boton>{{ $miPaciente ? 'Reservar cita' : 'Registrar cita' }}</x-boton>
                </div>
            </form>

            <x-info-cita />
        </div>
    </div>
</x-app-layout>
