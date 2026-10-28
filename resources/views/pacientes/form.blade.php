@php($editando = $paciente->exists)

<x-app-layout>
    <x-slot name="header">
        <h2>{{ $editando ? 'Modificar paciente ' . $paciente->codigo : 'Registrar paciente' }}</h2>
        <p>{{ $editando ? 'Actualice los datos del paciente.' : 'Complete los datos para dar de alta al paciente.' }}</p>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ $editando ? route('pacientes.update', $paciente) : route('pacientes.store') }}"
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

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-campo name="ci" label="CI (carnet de identidad)" :value="$paciente->ci" required maxlength="20"
                             pattern="[0-9A-Za-z\-]+" title="Solo letras, números y guiones" />

                    <x-campo-select name="sexo" label="Sexo" required>
                        <option value="">Seleccione…</option>
                        <option value="F" @selected(old('sexo', $paciente->sexo) === 'F')>Femenino</option>
                        <option value="M" @selected(old('sexo', $paciente->sexo) === 'M')>Masculino</option>
                    </x-campo-select>

                    <x-campo name="nombres" label="Nombres" :value="$paciente->nombres" required maxlength="100"
                             pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'\-]+" title="Solo letras y espacios" />

                    <x-campo name="apellidos" label="Apellidos" :value="$paciente->apellidos" required maxlength="100"
                             pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'\-]+" title="Solo letras y espacios" />

                    <x-campo name="fecha_nacimiento" label="Fecha de nacimiento" type="date"
                             :value="$paciente->fecha_nacimiento?->format('Y-m-d')" required
                             min="1900-01-02" :max="now()->format('Y-m-d')" />

                    <x-campo name="telefono" label="Teléfono" type="tel" :value="$paciente->telefono" required maxlength="20"
                             pattern="[0-9+\s\-]{7,20}" title="De 7 a 20 caracteres: números, espacios, + o guiones" />

                    <x-campo name="email" label="Correo electrónico" type="email" :value="$paciente->email" maxlength="150"
                             ayuda="Opcional. Se usará para enviar avisos al paciente." />

                    <x-campo name="direccion" label="Dirección" :value="$paciente->direccion" maxlength="255" />
                </div>

                <p class="text-xs text-gray-500"><span class="text-red-600">*</span> Campos obligatorios</p>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <x-boton tipo="secundario" :href="route('pacientes.index')">Cancelar</x-boton>
                    <x-boton>{{ $editando ? 'Guardar cambios' : 'Registrar paciente' }}</x-boton>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
