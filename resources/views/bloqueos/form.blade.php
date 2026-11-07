<x-app-layout>
    <x-slot name="header">
        <h2>Nuevo feriado o bloqueo</h2>
        <p>Cierre la agenda de toda la clínica o de un médico durante unos días.</p>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('bloqueos.store') }}" class="card-soft p-4 sm:p-8 space-y-5">
                @csrf

                @if ($errors->any())
                    <div role="alert" class="rounded-md border border-[rgb(var(--c-peligro)/.4)] bg-[rgb(var(--c-peligro)/.1)] p-3 text-sm text-[rgb(var(--c-peligro))]">
                        Revise los campos marcados: hay datos que no se pudieron guardar.
                    </div>
                @endif

                <x-campo-select name="medico_id" label="Quién no atiende">
                    <option value="">Toda la clínica (feriado)</option>
                    @foreach ($medicos as $medico)
                        <option value="{{ $medico->id }}" @selected((string) old('medico_id') === (string) $medico->id)>{{ $medico->apellidos }}, {{ $medico->nombres }}</option>
                    @endforeach
                </x-campo-select>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-campo name="fecha_inicio" label="Desde" type="date" required />
                    <x-campo name="fecha_fin" label="Hasta" type="date" required />
                </div>

                <x-campo name="motivo" label="Motivo" maxlength="120" required ayuda="Por ejemplo: Feriado nacional, Vacaciones, Capacitación." />

                <p class="text-xs text-gray-500"><span class="text-red-600">*</span> Campos obligatorios</p>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <x-boton tipo="secundario" :href="route('bloqueos.index')">Cancelar</x-boton>
                    <x-boton>Registrar bloqueo</x-boton>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
