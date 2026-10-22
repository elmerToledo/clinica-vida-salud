{{-- Contenedor de la barra de filtros de los listados (formulario GET). --}}
<form method="GET" {{ $attributes->merge(['class' => 'bg-white shadow sm:rounded-lg p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end']) }}>
    {{ $slot }}
</form>
