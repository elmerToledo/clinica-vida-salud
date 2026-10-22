{{-- Botón o enlace con aspecto de botón. Con href se dibuja un <a>; sin href, un <button>. --}}
@props(['tipo' => 'primario', 'href' => null])

@php
    $base = 'inline-flex items-center justify-center px-4 min-h-[44px] rounded-lg font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150';
    $variantes = [
        'primario' => 'bg-primary text-white hover:bg-deep focus:ring-primary',
        'secundario' => 'bg-white border border-gray-300 text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-primary',
        'peligro' => 'bg-red-700 text-white hover:bg-red-600 focus:ring-red-500',
    ];
    $clases = $base . ' ' . ($variantes[$tipo] ?? $variantes['primario']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $clases]) }}>{{ $slot }}</button>
@endif
