<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2>Mis avisos</h2>
                <p>Recordatorios de sus citas.</p>
            </div>
            @if (auth()->user()->unreadNotifications()->count() > 0)
                <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
                    @csrf
                    <x-boton tipo="secundario">Marcar todos como leídos</x-boton>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">
            @forelse ($notificaciones as $n)
                <div class="card-soft flex items-start gap-4 p-4 {{ $n->read_at ? '' : 'ring-2 ring-primary/30' }}">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"><x-icono nombre="bell" class="h-5 w-5" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-tinta">{{ $n->data['titulo'] ?? 'Aviso' }}</p>
                        <p class="text-sm text-suave">{{ $n->data['mensaje'] ?? '' }}</p>
                        <p class="mt-1 text-xs text-suave">{{ $n->created_at->timezone(\App\Services\CitaService::ZONA)->format('d/m/Y H:i') }}</p>
                    </div>
                    @unless ($n->read_at)
                        <form method="POST" action="{{ route('notificaciones.leer', $n->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-sm font-semibold text-primary hover:underline">Marcar leído</button>
                        </form>
                    @endunless
                </div>
            @empty
                <div class="card-soft p-8 text-center text-suave">No tiene avisos por ahora.</div>
            @endforelse

            {{ $notificaciones->links() }}
        </div>
    </div>
</x-app-layout>
