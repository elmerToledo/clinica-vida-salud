<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>Pagar cita <em>{{ $cita->codigo }}</em></h2>
            <x-boton tipo="secundario" :href="route('citas.show', $cita)">Volver a la cita</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="card-soft p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <span class="text-2xl font-extrabold tracking-tight" style="color:#003087">Pay<span style="color:#009cde">Pal</span></span>
                    <span class="text-xs rounded-full bg-amber-100 text-amber-800 px-3 py-1 font-semibold">Simulación</span>
                </div>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-600">Comercio</dt><dd class="font-semibold">Clínica Santa Aurora</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-600">Concepto</dt><dd class="font-semibold">Consulta de {{ $cita->especialidad->nombre }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-600">Médico</dt><dd class="font-semibold">{{ $cita->medico->apellidos }}, {{ $cita->medico->nombres }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-600">Fecha de la cita</dt><dd class="font-semibold">{{ $cita->fecha->format('d/m/Y') }} {{ $cita->inicio }}</dd></div>
                    <div class="flex justify-between border-t border-linea pt-3 text-base"><dt class="font-semibold">Total a pagar</dt><dd class="font-extrabold">Bs {{ number_format((float) $cita->especialidad->precio, 2) }}</dd></div>
                </dl>

                <p class="text-xs text-gray-500">Es una pasarela simulada para el proyecto: no se pide ni se guarda ninguna cuenta ni tarjeta y no se cobra dinero real. Al confirmar, el sistema genera el número de operación y registra el pago.</p>

                <form method="POST" action="{{ route('citas.pagar.confirmar', $cita) }}" class="flex flex-wrap gap-3">
                    @csrf
                    <x-boton>Pagar con PayPal</x-boton>
                    <x-boton tipo="secundario" :href="route('citas.show', $cita)">Cancelar</x-boton>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
