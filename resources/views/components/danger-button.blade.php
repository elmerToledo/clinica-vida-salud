<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 min-h-11 rounded-xl bg-[rgb(var(--c-peligro-bg))] text-[rgb(var(--c-peligro-texto))] font-semibold text-[15px] hover:opacity-90 focus:outline-none focus-visible:ring-4 focus-visible:ring-red-300 transition']) }}>
    {{ $slot }}
</button>
