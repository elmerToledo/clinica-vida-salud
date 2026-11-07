<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Historial médico de {{ $paciente->nombre_completo }}</h2>
                <p>CI {{ $paciente->ci }} · atenciones realizadas en la clínica.</p>
            </div>
            <x-boton tipo="secundario" :href="route('historial.pacientes')">Mis pacientes</x-boton>
        </div>
    </x-slot>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('historial._tabla')
        </div>
    </div>
</x-app-layout>
