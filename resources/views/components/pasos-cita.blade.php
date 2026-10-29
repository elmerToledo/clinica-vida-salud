{{-- Pasos de la cita (vertical): 1 Paciente, 2 Médico y horario, 3 Confirmación. --}}
@props(['paso' => 2, 'paciente' => null])

<ol class="flex gap-4 lg:flex-col lg:gap-5" aria-label="Pasos para agendar la cita">
    @foreach ([1 => 'Paciente', 2 => 'Médico y horario', 3 => 'Confirmación'] as $n => $titulo)
        <li class="flex items-center gap-3" @if ($n === $paso) aria-current="step" @endif>
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full border-2 text-sm font-bold
                {{ $n < $paso ? 'border-primary bg-primary text-sobre-primary' : ($n === $paso ? 'border-primary text-primary' : 'border-linea bg-tenue text-suave') }}">{{ $n < $paso ? '✓' : $n }}</span>
            <span class="{{ $n === $paso ? '' : 'hidden sm:block' }}">
                <span class="block text-xs text-suave">Paso {{ $n }}</span>
                <span class="block text-sm font-semibold {{ $n <= $paso ? 'text-tinta' : 'text-suave' }}">{{ $titulo }}</span>
                @if ($n === 1 && $paciente && $paso > 1)
                    <span class="block max-w-[10rem] truncate text-xs text-suave">{{ $paciente }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ol>
