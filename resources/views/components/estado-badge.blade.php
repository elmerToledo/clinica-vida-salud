{{-- Etiqueta de estado: Activo (verde) o Inactivo (gris). --}}
@props(['activo' => true])

@if ($activo)
    <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-ok">Activo</span>
@else
    <span class="inline-flex items-center rounded-full bg-gray-200 px-3 py-1 text-sm font-semibold text-gray-600">Inactivo</span>
@endif
