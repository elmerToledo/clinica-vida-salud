@php($fechaTexto = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $fecha)->format('d/m/Y'))

<x-app-layout>
    <x-slot name="header">
        <h2>{!! $miPaciente ? 'Reservar <em>cita</em>' : 'Nueva <em>cita</em>' !!}</h2>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('citas.store') }}" class="card-soft p-4 sm:p-8 space-y-5">
                @csrf
                <input type="hidden" name="medico_id" value="{{ $medico->id }}">
                <input type="hidden" name="fecha" value="{{ $fecha }}">
                <input type="hidden" name="hora_inicio" value="{{ $bloque['inicio'] }}">

                @if ($errors->any())
                    <div role="alert" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                        <ul class="list-disc ml-5">
                            @foreach ($errors->all() as $mensaje)
                                <li>{{ $mensaje }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="rounded-2xl bg-ambar-claro/40 border border-linea p-4 text-sm">
                    <div class="font-semibold text-gray-900">{{ $medico->nombres }} {{ $medico->apellidos }}</div>
                    <div class="text-gray-700">{{ $fechaTexto }} · {{ $bloque['inicio'] }} a {{ $bloque['fin'] }} ({{ $bloque['duracion'] }} min)</div>
                </div>

                @if ($miPaciente)
                    <div>
                        <div class="block font-semibold text-sm text-tinta">Paciente</div>
                        <div class="mt-1 campo bg-gray-50">{{ $miPaciente->nombres }} {{ $miPaciente->apellidos }} · CI {{ $miPaciente->ci }}</div>
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

                <p class="text-xs text-gray-500"><span class="text-red-600">*</span> Campos obligatorios</p>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <x-boton tipo="secundario" :href="route('citas.disponibilidad', ['medico' => $medico->id, 'fecha' => $fecha])">Volver</x-boton>
                    <x-boton>{{ $miPaciente ? 'Reservar cita' : 'Registrar cita' }}</x-boton>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
