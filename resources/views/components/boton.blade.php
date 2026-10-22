{{-- Botón o enlace con aspecto de botón. Con href se dibuja un <a>; sin href, un <button>. --}}
@props(['tipo' => 'primario', 'href' => null])

@php
    $base = 'inline-flex items-center justify-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150';
    $variantes = [
        'primario' => 'bg-teal-600 text-white hover:bg-teal-700 focus:ring-teal-500',
        'secundario' => 'bg-white border border-gray-300 text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-teal-500',
        'peligro' => 'bg-red-600 text-white hover:bg-red-500 focus:ring-red-500',
    ];
    $clases = $base . ' ' . ($variantes[$tipo] ?? $variantes['primario']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $clases]) }}>{{ $slot }}</button>
@endif
