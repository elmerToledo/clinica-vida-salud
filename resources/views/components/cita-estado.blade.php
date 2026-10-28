{{-- Etiqueta del estado de una cita: Programada (ámbar), Confirmada (verde), Atendida (azul), Cancelada (rojo). --}}
@props(['estado'])

@php
    $tono = match ($estado) {
        'confirmada' => 'bg-[rgb(var(--c-ok)/.14)] text-[rgb(var(--c-ok))]',
        'atendida' => 'bg-[rgb(var(--c-info)/.16)] text-[rgb(var(--c-info))]',
        'cancelada' => 'bg-[rgb(var(--c-peligro)/.14)] text-[rgb(var(--c-peligro))]',
        'inasistencia' => 'bg-tenue text-suave',
        default => 'bg-[rgb(var(--c-warn)/.16)] text-[rgb(var(--c-warn))]',
    };
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold {{ $tono }}">
    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ \App\Models\Cita::ESTADOS[$estado] ?? $estado }}
</span>
