{{-- Filtro de fechas de los reportes (formulario GET) con botón para imprimir. --}}
@props(['ruta', 'desde', 'hasta', 'opcional' => false])

<form method="GET" action="{{ route($ruta) }}" class="card-soft no-imprimir p-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
    <div>
        <x-input-label for="desde" value="Desde" />
        <input id="desde" name="desde" type="date" value="{{ $desde }}" class="mt-1 campo" @required(! $opcional)>
        <x-input-error :messages="$errors->get('desde')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="hasta" value="Hasta" />
        <input id="hasta" name="hasta" type="date" value="{{ $hasta }}" class="mt-1 campo" @required(! $opcional)>
        <x-input-error :messages="$errors->get('hasta')" class="mt-1" />
    </div>
    {{ $slot }}
    <div class="flex flex-wrap gap-2">
        <x-boton>Generar</x-boton>
        <button type="button" onclick="window.print()" class="inline-flex h-11 items-center gap-2 rounded-xl border border-linea px-4 text-sm font-semibold text-tinta hover:bg-tenue">
            <x-icono nombre="printer" class="h-4 w-4" /> Imprimir
        </button>
    </div>
</form>
