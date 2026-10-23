{{-- Casilla de verificación. Envía 0 si está desmarcada y 1 si está marcada. --}}
@props(['name', 'label', 'checked' => false, 'ayuda' => null])

<div {{ $attributes->only('class') }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="inline-flex items-start gap-2">
        <input
            type="checkbox"
            name="{{ $name }}"
            value="1"
            @checked(old($name, $checked) ? true : false)
            class="mt-0.5 h-5 w-5 rounded border-gray-400 text-primary shadow-sm focus:ring-primary"
        >
        <span class="text-sm text-gray-700">
            {{ $label }}
            @if ($ayuda)
                <span class="block text-xs text-gray-500">{{ $ayuda }}</span>
            @endif
        </span>
    </label>
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
