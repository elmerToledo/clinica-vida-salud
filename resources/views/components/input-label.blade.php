@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-tinta']) }}>
    {{ $value ?? $slot }}
</label>
