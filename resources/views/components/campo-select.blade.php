{{-- Lista desplegable con etiqueta y mensaje de error. Las <option> van en el contenido. --}}
@props(['name', 'label', 'required' => false, 'ayuda' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block font-semibold text-sm text-tinta">
        {{ $label }}@if ($required)<span class="text-red-600"> *</span>@endif
    </label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @required($required)
        {{ $attributes->except('class')->merge(['class' => 'mt-1 campo']) }}
    >
        {{ $slot }}
    </select>
    @if ($ayuda)
        <p class="mt-1 text-xs text-gray-500">{{ $ayuda }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
