@php($editando = $especialidad->exists)

<x-app-layout>
    <x-slot name="header">
        <h2>
            {{ $editando ? 'Modificar especialidad' : 'Nueva especialidad' }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ $editando ? route('especialidades.update', $especialidad) : route('especialidades.store') }}"
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

                <x-campo name="nombre" label="Nombre de la especialidad" :value="$especialidad->nombre" required maxlength="100"
                         ayuda="No se admiten dos especialidades con el mismo nombre (sin importar mayúsculas ni tildes)." />

                <div>
                    <x-input-label for="descripcion" value="Descripción" />
                    <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"
                              class="mt-1 campo">{{ old('descripcion', $especialidad->descripcion) }}</textarea>
                    <x-input-error :messages="$errors->get('descripcion')" class="mt-1" />
                </div>

                <p class="text-xs text-gray-500"><span class="text-red-600">*</span> Campos obligatorios</p>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <x-boton tipo="secundario" :href="route('especialidades.index')">Cancelar</x-boton>
                    <x-boton>{{ $editando ? 'Guardar cambios' : 'Registrar especialidad' }}</x-boton>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
