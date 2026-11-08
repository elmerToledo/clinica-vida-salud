<x-tabla>
    <thead class="text-left text-xs font-bold uppercase tracking-wider text-suave">
        <tr>
            <th class="px-5 py-4">Fecha</th>
            <th class="px-5 py-4">Especialidad</th>
            <th class="px-5 py-4">Médico</th>
            <th class="px-5 py-4">Motivo de la consulta</th>
            <th class="px-5 py-4">Receta</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-linea">
        @forelse ($atenciones as $a)
            <tr>
                <td class="px-5 py-4 font-semibold text-tinta">{{ $a->fecha->format('d/m/Y') }} {{ $a->inicio }}</td>
                <td class="px-5 py-4">{{ $a->especialidad->nombre }}</td>
                <td class="px-5 py-4">{{ $a->medico->apellidos }}, {{ $a->medico->nombres }}</td>
                <td class="px-5 py-4">{{ $a->motivo_consulta ?: '—' }}</td>
                <td class="px-5 py-4">
                    @php($u = auth()->user())
                    @if ($a->receta && ($u->tieneRol('medico') ? $u->medico?->id === $a->medico_id : $a->receta->publicada))
                        <a href="{{ route('recetas.imprimir', $a) }}" class="font-semibold text-primary underline" target="_blank" rel="noopener">Ver receta</a>
                    @else
                        —
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-8 text-center text-suave">Aún no hay atenciones registradas.</td></tr>
        @endforelse
    </tbody>
</x-tabla>
