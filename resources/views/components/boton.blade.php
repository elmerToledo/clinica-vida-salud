{{-- Botón o enlace con aspecto de botón. Con href se dibuja un <a>; sin href, un <button>. --}}
@props(['tipo' => 'primario', 'href' => null])

@php
    $base = 'inline-flex items-center justify-center gap-2 px-6 min-h-12 rounded-full font-semibold text-base whitespace-nowrap transition-all duration-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-ambar/50';
    $variantes = [
        'primario' => 'bg-ambar text-tinta hover:brightness-105 hover:-translate-y-0.5',
        'secundario' => 'border border-tinta/25 bg-transparent text-tinta hover:bg-tinta/5',
        'peligro' => 'bg-red-700 text-white hover:bg-red-600',
    ];
    $clases = $base . ' ' . ($variantes[$tipo] ?? $variantes['primario']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $clases]) }}>{{ $slot }}</button>
@endif
