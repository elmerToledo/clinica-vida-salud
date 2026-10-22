@php($editando = $medico->exists)

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $editando ? 'Modificar médico ' . $medico->codigo : 'Nuevo médico' }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form id="form-medico" method="POST" action="{{ $editando ? route('medicos.update', $medico) : route('medicos.store') }}"
                  class="bg-white shadow sm:rounded-lg p-4 sm:p-8 space-y-5">
                @csrf
                @if ($editando)
                    @method('PUT')
                @endif

                @if ($errors->any())
                    <div role="alert" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                        Revise los campos marcados: hay datos que no se pudieron guardar.
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-campo name="nombres" label="Nombres" :value="$medico->nombres" required maxlength="100"
                             pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'\-]+" title="Solo letras y espacios" />

                    <x-campo name="apellidos" label="Apellidos" :value="$medico->apellidos" required maxlength="100"
                             pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'\-]+" title="Solo letras y espacios" />

                    <x-campo name="registro_profesional" label="Registro profesional" :value="$medico->registro_profesional" required maxlength="30"
                             ayuda="Identifica al médico; no puede repetirse." />

                    <x-campo name="telefono" label="Teléfono" type="tel" :value="$medico->telefono" maxlength="20"
                             pattern="[0-9+\s\-]{7,20}" title="De 7 a 20 caracteres: números, espacios, + o guiones" />

                    <x-campo name="email" label="Correo electrónico" type="email" :value="$medico->email" maxlength="150" class="sm:col-span-2" />
                </div>

                <!-- Especialidades: al menos una (se valida también en el navegador con el script de abajo) -->
                <fieldset>
                    <legend class="block font-medium text-sm text-gray-700">Especialidades <span class="text-red-600">*</span></legend>
                    @if ($especialidades->isEmpty())
                        <p class="mt-2 text-sm text-red-700">No hay especialidades activas. Registre una especialidad antes de crear médicos.</p>
                    @else
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($especialidades as $especialidad)
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="especialidades[]" value="{{ $especialidad->id }}"
                                           @checked(in_array($especialidad->id, array_map('intval', (array) old('especialidades', $seleccionadas))))
                                           class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500">
                                    {{ $especialidad->nombre }}@unless ($especialidad->activo) <span class="text-xs text-gray-500">(inactiva)</span>@endunless
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <x-input-error :messages="$errors->get('especialidades')" class="mt-1" />
                    <x-input-error :messages="$errors->get('especialidades.*')" class="mt-1" />
                </fieldset>

                <!-- Cuenta de acceso opcional (para que el médico consulte su propia agenda) -->
                <x-campo-select name="user_id" label="Cuenta de acceso" ayuda="Opcional. Solo aparecen cuentas con rol Médico que aún no están asociadas a otro médico.">
                    <option value="">Sin cuenta asociada</option>
                    @foreach ($cuentas as $cuenta)
                        <option value="{{ $cuenta->id }}" @selected((string) old('user_id', $medico->user_id) === (string) $cuenta->id)>{{ $cuenta->name }} ({{ $cuenta->usuario }})</option>
                    @endforeach
                </x-campo-select>

                <!-- CU30: permiso para marcar citas como atendidas -->
                <x-campo-check name="puede_marcar_atendida" label="Puede marcar citas como atendidas"
                               :checked="$medico->puede_marcar_atendida"
                               ayuda="Si no se marca, el médico solo consulta su agenda." />

                <p class="text-xs text-gray-500"><span class="text-red-600">*</span> Campos obligatorios</p>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <x-boton tipo="secundario" :href="route('medicos.index')">Cancelar</x-boton>
                    <x-boton>{{ $editando ? 'Guardar cambios' : 'Registrar médico' }}</x-boton>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validación en el navegador: debe marcarse al menos una especialidad.
        document.addEventListener('DOMContentLoaded', function () {
            var casillas = document.querySelectorAll('#form-medico input[name="especialidades[]"]');
            if (casillas.length === 0) { return; }

            function validar() {
                var alguna = Array.prototype.some.call(casillas, function (c) { return c.checked; });
                casillas[0].setCustomValidity(alguna ? '' : 'Seleccione al menos una especialidad.');
            }

            casillas.forEach(function (c) { c.addEventListener('change', validar); });
            validar();
        });
    </script>
</x-app-layout>
