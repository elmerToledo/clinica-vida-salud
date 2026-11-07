<x-app-layout>
    <x-slot name="header">
        <h2>Mi historial médico</h2>
        <p>Las consultas en las que ya fue atendido en la clínica.</p>
    </x-slot>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('historial._tabla')
        </div>
    </div>
</x-app-layout>
