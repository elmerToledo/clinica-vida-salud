<x-app-layout>
    <x-slot name="header">
        <h2>Reportes</h2>
        <p>Consulte cómo va la clínica. Los reportes solo muestran datos: no modifican nada.</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($reportes as $reporte)
                    <a href="{{ route($reporte['ruta']) }}" class="card-soft card-lift flex items-start gap-4 p-5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"><x-icono :nombre="$reporte['icono']" class="h-5 w-5" /></span>
                        <span class="min-w-0">
                            <span class="block text-base font-bold text-tinta">{{ $reporte['etiqueta'] }}</span>
                            <span class="mt-0.5 block text-sm text-suave">{{ $reporte['descripcion'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
