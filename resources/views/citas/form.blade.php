@php($fechaTexto = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $fecha)->format('d/m/Y'))

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2>{{ $miPaciente ? 'Reservar cita' : 'Nueva cita' }}</h2>
            <p>Confirme los datos para dejar la cita registrada.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-pasos-cita :paso="3" />
            <form method="POST" action="{{ route('citas.store') }}" class="card-soft p-4 sm:p-8 space-y-5">
                @csrf
                <input type="hidden" name="medico_id" value="{{ $medico->id }}">
                <input type="hidden" name="fecha" value="{{ $fecha }}">
                <input type="hidden" name="hora_inicio" value="{{ $bloque['inicio'] }}">

                @if ($errors->any())
                    <div role="alert" class="rounded-md border border-[rgb(var(--c-peligro)/.4)] bg-[rgb(var(--c-peligro)/.1)] p-3 text-sm text-[rgb(var(--c-peligro))]">
                        <ul class="list-disc ml-5">
                            @foreach ($errors->all() as $mensaje)
                                <li>{{ $mensaje }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="rounded-2xl bg-tenue border border-linea p-4 text-sm">
                    <div class="font-semibold text-tinta">{{ $medico->nombres }} {{ $medico->apellidos }}</div>
                    <div class="text-suave">{{ $fechaTexto }} · {{ $bloque['inicio'] }} a {{ $bloque['fin'] }} ({{ $bloque['duracion'] }} min)</div>
                </div>

                @if ($miPaciente)
                    <div>
                        <div class="block font-semibold text-sm text-tinta">Paciente</div>
                        <div class="mt-1 campo bg-tenue">{{ $miPaciente->nombres }} {{ $miPaciente->apellidos }} · CI {{ $miPaciente->ci }}</div>
                    </div>
                @else
                    <x-campo-select name="paciente_id" label="Paciente" required>
                        <option value="">Seleccione…</option>
                        @foreach ($pacientes as $paciente)
                            <option value="{{ $paciente->id }}" @selected((string) old('paciente_id') === (string) $paciente->id)>
                                {{ $paciente->apellidos }}, {{ $paciente->nombres }} · CI {{ $paciente->ci }}
                            </option>
                        @endforeach
                    </x-campo-select>
                @endif

                <x-campo-select name="especialidad_id" label="Especialidad de la consulta" required>
                    <option value="">Seleccione…</option>
                    @foreach ($medico->especialidades as $especialidad)
                        <option value="{{ $especialidad->id }}" @selected((string) old('especialidad_id', $medico->especialidades->count() === 1 ? $especialidad->id : null) === (string) $especialidad->id)>
                            {{ $especialidad->nombre }}
                        </option>
                    @endforeach
                </x-campo-select>

                <div>
                    <label for="motivo_consulta" class="block font-semibold text-sm text-tinta">Motivo de la consulta (opcional)</label>
                    <textarea id="motivo_consulta" name="motivo_consulta" rows="3" maxlength="500" class="mt-1 campo">{{ old('motivo_consulta') }}</textarea>
                    <x-input-error :messages="$errors->get('motivo_consulta')" class="mt-1" />
                </div>

                <p class="text-xs text-suave"><span class="text-red-600">*</span> Campos obligatorios</p>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <x-boton tipo="secundario" :href="route('citas.disponibilidad', ['medico' => $medico->id, 'fecha' => $fecha])">Volver</x-boton>
                    <x-boton>{{ $miPaciente ? 'Reservar cita' : 'Registrar cita' }}</x-boton>
                </div>
            </form>

            <x-consejos-cita />
        </div>
    </div>
</x-app-layout>
