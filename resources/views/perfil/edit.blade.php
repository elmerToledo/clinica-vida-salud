<x-app-layout>
    <x-slot name="header">
        <h2>{!! $completando ? 'Complete su <em>perfil</em>' : 'Mis <em>datos</em>' !!}</h2>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if ($completando)
                <div class="rounded-2xl border border-linea bg-ambar-claro/40 p-4 text-sm text-gray-800">
                    Para reservar citas necesitamos sus datos de paciente. Complételos una sola vez.
                </div>
            @endif

            <form method="POST" action="{{ route('perfil.update') }}" class="card-soft p-4 sm:p-8 space-y-5">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div role="alert" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
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

                    <x-campo name="email" label="Correo de contacto" type="email" :value="$paciente->email" maxlength="150"
                             ayuda="Opcional. Es distinto del correo con el que ingresa al sistema." />

                    <x-campo name="direccion" label="Dirección" :value="$paciente->direccion" maxlength="255" />
                </div>

                <p class="text-xs text-gray-500"><span class="text-red-600">*</span> Campos obligatorios</p>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <x-boton tipo="secundario" :href="route('dashboard')">Cancelar</x-boton>
                    <x-boton>{{ $completando ? 'Guardar y continuar' : 'Guardar cambios' }}</x-boton>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
