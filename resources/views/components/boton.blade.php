{{-- Botón o enlace con aspecto de botón. Con href se dibuja un <a>; sin href, un <button>. --}}
@props(['tipo' => 'primario', 'href' => null])

@php
    $base = 'inline-flex items-center justify-center gap-2 px-5 min-h-11 rounded-xl font-semibold text-[15px] whitespace-nowrap transition-colors duration-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/30';
    $variantes = [
        'primario' => 'bg-primary text-sobre-primary hover:opacity-90',
        'secundario' => 'border border-linea bg-tarjeta text-tinta hover:bg-tenue',
        'peligro' => 'bg-[rgb(var(--c-peligro-bg))] text-[rgb(var(--c-peligro-texto))] hover:opacity-90',
    ];
    $clases = $base . ' ' . ($variantes[$tipo] ?? $variantes['primario']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $clases]) }}>{{ $slot }}</button>
@endif
