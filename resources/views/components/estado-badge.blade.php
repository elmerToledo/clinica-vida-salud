{{-- Etiqueta de estado: Activo (verde) o Inactivo (gris). --}}
@props(['activo' => true])

@if ($activo)
    <span class="inline-flex items-center gap-1.5 rounded-full bg-[rgb(var(--c-ok)/.14)] px-3 py-1 text-sm font-semibold text-[rgb(var(--c-ok))]"><span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>Activo</span>
@else
    <span class="inline-flex items-center gap-1.5 rounded-full bg-tenue px-3 py-1 text-sm font-semibold text-suave"><span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>Inactivo</span>
@endif
