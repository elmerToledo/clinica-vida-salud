@php($editando = $horario->exists)

<x-app-layout>
    <x-slot name="header">
        <h2>
            {{ $editando ? 'Modificar horario de atención' : 'Nuevo horario de atención' }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form id="form-horario" method="POST" action="{{ $editando ? route('horarios.update', $horario) : route('horarios.store') }}"
                  class="card-soft p-4 sm:p-8 space-y-5">
                @csrf
                @if ($editando)
                    @method('PUT')
                @endif

                @if ($errors->any())
                    <div role="alert" class="rounded-md border border-[rgb(var(--c-peligro)/.4)] bg-[rgb(var(--c-peligro)/.1)] p-3 text-sm text-[rgb(var(--c-peligro))]">
                        Revise los campos marcados: hay datos que no se pudieron guardar.
                    </div>
                @endif

                <x-campo-select name="medico_id" label="Médico" required>
                    <option value="">Seleccione…</option>
                    @foreach ($medicos as $medico)
                        <option value="{{ $medico->id }}" @selected((string) old('medico_id', $horario->medico_id) === (string) $medico->id)>
                            {{ $medico->apellidos }}, {{ $medico->nombres }}{{ $medico->activo ? '' : ' (inactivo)' }}
                        </option>
                    @endforeach
                </x-campo-select>

                <x-campo-select name="dia_semana" label="Día de la semana" required>
                    <option value="">Seleccione…</option>
                    @foreach (\App\Models\HorarioAtencion::DIAS as $numero => $nombre)
                        <option value="{{ $numero }}" @selected((string) old('dia_semana', $horario->dia_semana) === (string) $numero)>{{ $nombre }}</option>
                    @endforeach
                </x-campo-select>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-campo name="hora_inicio" label="Hora inicial" type="time" :value="$horario->hora_inicio ? substr($horario->hora_inicio, 0, 5) : null" required />
                    <x-campo name="hora_fin" label="Hora final" type="time" :value="$horario->hora_fin ? substr($horario->hora_fin, 0, 5) : null" required />
                </div>

                <x-campo name="duracion_cita_min" label="Duración de cada cita (minutos)" type="number"
                         :value="$horario->duracion_cita_min ?? 30" required min="5" max="240" step="5"
                         ayuda="Entre 5 y 240 minutos. El horario debe permitir al menos una cita completa." />

                <p class="text-xs text-gray-500"><span class="text-red-600">*</span> Campos obligatorios</p>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <x-boton tipo="secundario" :href="route('horarios.index', ['medico' => $horario->medico_id])">Cancelar</x-boton>
                    <x-boton>{{ $editando ? 'Guardar cambios' : 'Registrar horario' }}</x-boton>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validación en el navegador: hora final posterior a la inicial y duración que quepa en el horario.
        document.addEventListener('DOMContentLoaded', function () {
            var inicio = document.getElementById('hora_inicio');
            var fin = document.getElementById('hora_fin');
            var duracion = document.getElementById('duracion_cita_min');

            function minutos(texto) {
                var partes = texto.split(':');
                return parseInt(partes[0], 10) * 60 + parseInt(partes[1], 10);
            }

            function validar() {
                fin.setCustomValidity('');
                duracion.setCustomValidity('');
                if (!inicio.value || !fin.value) { return; }

                var total = minutos(fin.value) - minutos(inicio.value);
                if (total <= 0) {
                    fin.setCustomValidity('La hora final debe ser posterior a la hora inicial.');
                } else if (duracion.value && total < parseInt(duracion.value, 10)) {
                    duracion.setCustomValidity('La duración de la cita no cabe en el horario indicado.');
                }
            }

            [inicio, fin, duracion].forEach(function (campo) { campo.addEventListener('input', validar); });
            validar();
        });
    </script>
</x-app-layout>
