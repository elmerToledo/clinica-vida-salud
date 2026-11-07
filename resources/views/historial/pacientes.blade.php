<x-app-layout>
    <x-slot name="header">
        <h2>Mis pacientes</h2>
        <p>Pacientes que usted ya atendió. Abra uno para ver su historial médico.</p>
    </x-slot>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-tabla>
                <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
                    <tr><th class="px-5 py-4">Paciente</th><th class="px-5 py-4">CI</th><th class="px-5 py-4 text-right">Historial</th></tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($pacientes as $p)
                        <tr>
                            <td class="px-5 py-4 font-semibold text-tinta">{{ $p->nombre_completo }}</td>
                            <td class="px-5 py-4">{{ $p->ci }}</td>
                            <td class="px-5 py-4 text-right"><a class="text-sm font-semibold text-primary hover:underline" href="{{ route('historial.paciente', $p) }}">Ver historial</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-suave">Aún no ha atendido pacientes.</td></tr>
                    @endforelse
                </tbody>
            </x-tabla>
        </div>
    </div>
</x-app-layout>
