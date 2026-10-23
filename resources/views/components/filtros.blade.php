{{-- Contenedor de la barra de filtros de los listados (formulario GET). --}}
<form method="GET" {{ $attributes->merge(['class' => 'card-soft p-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end']) }}>
    {{ $slot }}
</form>
