{{-- Contenedor de tablas: en pantallas pequeñas se desplaza horizontalmente. --}}
<div class="card-soft overflow-hidden">
    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-linea text-[15px]']) }}>
            {{ $slot }}
        </table>
    </div>
</div>
