<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-6 min-h-12 bg-red-700 rounded-full font-semibold text-base text-white hover:bg-red-600 focus:outline-none focus-visible:ring-4 focus-visible:ring-red-300 transition']) }}>
    {{ $slot }}
</button>
