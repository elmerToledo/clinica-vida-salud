{{-- Etiqueta de estado: Activo (verde) o Inactivo (gris). --}}
@props(['activo' => true])

@if ($activo)
    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Activo</span>
@else
    <span class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-700">Inactivo</span>
@endif
