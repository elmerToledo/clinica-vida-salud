{{-- Logo de Clínica Santa Aurora: sol naciente. light = versión para fondos oscuros. --}}
@props(['light' => false, 'texto' => true])

<span {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 40 40" class="h-10 w-10 shrink-0" aria-hidden="true">
        <circle cx="20" cy="20" r="20" fill="#f4d9b5"/>
        <path d="M10 25a10 10 0 0 1 20 0z" fill="#e8963e"/>
        <path d="M7 25.5h26" stroke="#083f45" stroke-width="2" stroke-linecap="round"/>
        <path d="M20 9v3M11.5 13l2 2M28.5 13l-2 2" stroke="#e8963e" stroke-width="2" stroke-linecap="round"/>
    </svg>
    @if ($texto)
        <span class="font-display text-xl font-semibold leading-none {{ $light ? 'text-crema' : 'text-deep' }}">
            Santa Aurora
            <span class="block font-sans text-[11px] font-medium uppercase tracking-[0.18em] {{ $light ? 'text-ambar-claro' : 'text-suave' }}">Clínica médica</span>
        </span>
    @endif
</span>
