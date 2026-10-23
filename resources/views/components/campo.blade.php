{{-- Campo de formulario: etiqueta + <input> + mensaje de error del servidor. Los atributos extra (maxlength, pattern, etc.) pasan al <input>. --}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'ayuda' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block font-semibold text-sm text-tinta">
        {{ $label }}@if ($required)<span class="text-red-600"> *</span>@endif
    </label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @required($required)
        {{ $attributes->except('class')->merge(['class' => 'mt-1 campo']) }}
    >
    @if ($ayuda)
        <p class="mt-1 text-xs text-gray-500">{{ $ayuda }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
