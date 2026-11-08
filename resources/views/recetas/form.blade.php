<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>Receta médica · cita <em>{{ $cita->codigo }}</em></h2>
            <x-boton tipo="secundario" :href="route('citas.show', $cita)">Volver a la cita</x-boton>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('exito'))
                <div role="status" class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('exito') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $mensaje)
                            <li>{{ $mensaje }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card-soft p-5 text-sm">
                <div><strong>Paciente:</strong> {{ $cita->paciente->apellidos }}, {{ $cita->paciente->nombres }}</div>
                <div><strong>Especialidad:</strong> {{ $cita->especialidad->nombre }} · {{ $cita->fecha->format('d/m/Y') }} {{ $cita->inicio }}</div>
            </div>

            @if ($receta?->publicada)
                <div class="card-soft p-5 space-y-3 text-sm">
                    <h3 class="font-semibold text-gray-900">Receta <em>{{ $receta->codigo }}</em> (publicada)</h3>
                    <div><span class="text-xs font-bold uppercase tracking-wider text-deep">Diagnóstico</span><p class="mt-1 whitespace-pre-line">{{ $receta->diagnostico }}</p></div>
                    <div><span class="text-xs font-bold uppercase tracking-wider text-deep">Medicamentos</span><p class="mt-1 whitespace-pre-line">{{ $receta->medicamentos }}</p></div>
                    @if ($receta->indicaciones)
                        <div><span class="text-xs font-bold uppercase tracking-wider text-deep">Indicaciones</span><p class="mt-1 whitespace-pre-line">{{ $receta->indicaciones }}</p></div>
                    @endif
                    <x-boton :href="route('recetas.imprimir', $cita)" target="_blank" rel="noopener">Imprimir receta</x-boton>
                </div>
            @else
                <form method="POST" action="{{ route('recetas.guardar', $cita) }}" class="card-soft p-5 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="diagnostico" class="block font-semibold text-sm text-tinta">Diagnóstico <span class="text-red-600">*</span></label>
                        <textarea id="diagnostico" name="diagnostico" rows="2" maxlength="500" required class="mt-1 campo">{{ old('diagnostico', $receta?->diagnostico) }}</textarea>
                    </div>
                    <div>
                        <label for="medicamentos" class="block font-semibold text-sm text-tinta">Medicamentos y dosis <span class="text-red-600">*</span></label>
                        <textarea id="medicamentos" name="medicamentos" rows="5" maxlength="2000" required class="mt-1 campo">{{ old('medicamentos', $receta?->medicamentos) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Un medicamento por línea. Ejemplo: Paracetamol 500 mg, 1 tableta cada 8 horas por 3 días.</p>
                    </div>
                    <div>
                        <label for="indicaciones" class="block font-semibold text-sm text-tinta">Indicaciones</label>
                        <textarea id="indicaciones" name="indicaciones" rows="3" maxlength="1000" class="mt-1 campo">{{ old('indicaciones', $receta?->indicaciones) }}</textarea>
                    </div>
                    <x-boton>Guardar receta</x-boton>
                </form>

                @if ($receta)
                    <div class="card-soft p-5 space-y-3">
                        <h3 class="font-semibold text-gray-900">Publicar al paciente</h3>
                        <p class="text-sm text-gray-600">Receta <em>{{ $receta->codigo }}</em> guardada como borrador. Al publicarla el paciente la verá en su cita y en su historial, y ya no se podrá modificar.</p>
                        <div class="flex flex-wrap gap-3">
                            <form method="POST" action="{{ route('recetas.publicar', $cita) }}">
                                @csrf
                                @method('PATCH')
                                <x-boton>Publicar receta</x-boton>
                            </form>
                            <x-boton tipo="secundario" :href="route('recetas.imprimir', $cita)" target="_blank" rel="noopener">Imprimir receta</x-boton>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
