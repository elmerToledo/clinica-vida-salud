<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2>Nuestros <em>médicos</em></h2>
            @if (auth()->user()->tieneRol('administrador'))
                <x-boton :href="route('medicos.create')">Nuevo médico</x-boton>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <!-- CU13: búsqueda por nombre o especialidad -->
            <x-filtros>
                <div>
                    <x-input-label for="q" value="Buscar" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="request('q')" placeholder="Nombre o especialidad" maxlength="100" />
                </div>
                <div>
                    <x-input-label for="especialidad" value="Especialidad" />
                    <select id="especialidad" name="especialidad" class="mt-1 campo">
                        <option value="">Todas</option>
                        @foreach ($especialidades as $especialidad)
                            <option value="{{ $especialidad->id }}" @selected((string) $especialidadId === (string) $especialidad->id)>{{ $especialidad->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="estado" value="Estado" />
                    <select id="estado" name="estado" class="mt-1 campo">
                        <option value="todos" @selected($estado === 'todos')>Todos</option>
                        <option value="activos" @selected($estado === 'activos')>Activos</option>
                        <option value="inactivos" @selected($estado === 'inactivos')>Inactivos</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-boton>Buscar</x-boton>
                    <x-boton tipo="secundario" :href="route('medicos.index')">Limpiar</x-boton>
                </div>
            </x-filtros>

            @php($esAdmin = auth()->user()->tieneRol('administrador'))
            @if ($medicos->isEmpty())
                <div class="card-soft p-10 text-center text-suave">No se encontraron médicos.</div>
            @else
                <!-- Directorio de médicos en tarjetas -->
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($medicos as $medico)
                        <article class="card-soft flex flex-col gap-4 p-5 {{ $medico->activo ? '' : 'opacity-70' }}">
                            <div class="flex items-start gap-4">
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/15 text-lg font-bold text-primary" aria-hidden="true">{{ mb_strtoupper(mb_substr($medico->nombres, 0, 1) . mb_substr($medico->apellidos, 0, 1)) }}</span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-base font-bold text-tinta">{{ $medico->nombres }} {{ $medico->apellidos }}</h3>
                                    <p class="text-sm text-suave">{{ $medico->codigo }} · Reg. {{ $medico->registro_profesional }}</p>
                                    <p class="text-sm text-suave">Tel. {{ $medico->telefono }}</p>
                                </div>
                                <x-estado-badge :activo="$medico->activo" />
                            </div>

                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($medico->especialidades as $especialidad)
                                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">{{ $especialidad->nombre }}</span>
                                @empty
                                    <span class="text-xs text-suave">Sin especialidades</span>
                                @endforelse
                            </div>

                            <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-linea pt-4">
                                @if ($esAdmin)
                                    <a href="{{ route('medicos.edit', $medico) }}" class="inline-flex h-9 items-center rounded-lg border border-linea px-3 text-sm font-semibold text-tinta hover:bg-tenue">Editar</a>
                                @endif
                                <a href="{{ route('horarios.index', ['medico' => $medico->id]) }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-linea px-3 text-sm font-semibold text-tinta hover:bg-tenue">
                                    <x-icono nombre="clock" class="h-4 w-4" /> Horarios
                                </a>
                                @if ($esAdmin)
                                    <form method="POST" action="{{ route('medicos.estado', $medico) }}" class="ml-auto"
                                          onsubmit="return confirm('{{ $medico->activo ? '¿Desactivar este médico? Ya no recibirá citas nuevas; su historial se conserva.' : '¿Activar este médico?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="accion {{ $medico->activo ? 'accion-peligro' : 'accion-ok' }}">
                                            {{ $medico->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <div>{{ $medicos->links() }}</div>
        </div>
    </div>
</x-app-layout>
