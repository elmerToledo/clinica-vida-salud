{{-- Etiqueta del estado de una cita. --}}
@props(['estado'])

@php
    $tono = match ($estado) {
        'confirmada' => 'bg-teal-100 text-deep',
        'atendida' => 'bg-green-100 text-ok',
        'cancelada' => 'bg-gray-200 text-gray-600',
        'inasistencia' => 'bg-red-100 text-red-800',
        default => 'bg-ambar-claro text-tinta',
    };
@endphp

<span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ $tono }}">
    {{ \App\Models\Cita::ESTADOS[$estado] ?? $estado }}
</span>
