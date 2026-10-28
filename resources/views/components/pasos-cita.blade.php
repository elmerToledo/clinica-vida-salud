{{-- Indicador de los 3 pasos para reservar una cita: Médico y fecha, Horario, Confirmar. --}}
@props(['paso' => 1])

<ol class="card-soft p-4 sm:p-5 flex items-center gap-2 sm:gap-4" aria-label="Pasos para agendar la cita">
    @foreach ([1 => 'Médico y fecha', 2 => 'Horario', 3 => 'Confirmar'] as $n => $titulo)
        <li class="flex items-center gap-2 sm:gap-3 {{ $n < 3 ? 'flex-1' : '' }}" @if ($n === $paso) aria-current="step" @endif>
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-sm font-bold
                {{ $n < $paso ? 'bg-primary text-sobre-primary' : ($n === $paso ? 'bg-primary text-sobre-primary ring-4 ring-primary/20' : 'bg-tenue text-suave') }}">{{ $n < $paso ? '✓' : $n }}</span>
            <span class="text-sm font-semibold {{ $n <= $paso ? 'text-tinta' : 'text-suave' }} {{ $n === $paso ? '' : 'hidden sm:inline' }}">{{ $titulo }}</span>
            @if ($n < 3)
                <span class="h-px flex-1 {{ $n < $paso ? 'bg-primary' : 'bg-linea' }}" aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
