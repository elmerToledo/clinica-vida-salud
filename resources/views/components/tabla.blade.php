{{-- Contenedor de tablas: en pantallas pequeñas se desplaza horizontalmente. --}}
<div class="bg-white shadow sm:rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-gray-200 text-sm']) }}>
            {{ $slot }}
        </table>
    </div>
</div>
