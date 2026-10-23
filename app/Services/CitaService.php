<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de las citas médicas (RF06 a RF10, RN03 a RN13).
 *
 * Las horas se manejan como texto "HH:MM"; al ser del mismo formato,
 * compararlas como texto equivale a compararlas como horas.
 */
class CitaService
{
    public const ZONA = 'America/La_Paz';

    public function __construct(private HorarioService $horarios)
    {
    }

    /** Fecha y hora actuales en la zona horaria de la clínica. */
    public function ahora(): CarbonImmutable
    {
        return CarbonImmutable::now(self::ZONA);
    }

    public function hoy(): string
    {
        return $this->ahora()->toDateString();
    }

    /**
     * CU24: bloques de atención de un médico en una fecha.
     * Cada bloque: inicio, fin, estado ('libre', 'ocupado' o 'pasado') y duracion.
     * Un médico inactivo no tiene bloques (RN04). Las citas canceladas no ocupan (RN09).
     *
     * @return Collection<int, array{inicio: string, fin: string, estado: string, duracion: int}>
     */
    public function bloques(Medico $medico, string $fecha, ?int $ignorarCitaId = null): Collection
    {
        $bloques = collect();

        if (! $medico->activo) {
            return $bloques;
        }

        $dia = CarbonImmutable::createFromFormat('Y-m-d', $fecha, self::ZONA)->dayOfWeekIso;
        $ahora = $this->ahora();

        $ocupadas = Cita::query()
            ->where('medico_id', $medico->id)
            ->where('fecha', $fecha)
            ->ocupan()
            ->when($ignorarCitaId, fn ($q) => $q->where('id', '!=', $ignorarCitaId))
            ->get(['id', 'hora_inicio', 'hora_fin']);

        $franjas = HorarioAtencion::query()
            ->where('medico_id', $medico->id)
            ->where('dia_semana', $dia)
            ->where('activo', true)
            ->orderBy('hora_inicio')
            ->get();

        foreach ($franjas as $franja) {
            $desde = $this->aMinutos(substr($franja->hora_inicio, 0, 5));
            $hasta = $this->aMinutos(substr($franja->hora_fin, 0, 5));
            $duracion = $franja->duracion_cita_min;

            for ($m = $desde; $m + $duracion <= $hasta; $m += $duracion) {
                $inicio = $this->aTexto($m);
                $fin = $this->aTexto($m + $duracion);

                // RN06: no se programan citas en fechas u horas pasadas.
                $pasado = CarbonImmutable::createFromFormat('Y-m-d H:i', "$fecha $inicio", self::ZONA) <= $ahora;
                $ocupado = $ocupadas->contains(
                    fn (Cita $c) => $inicio < substr($c->hora_fin, 0, 5) && $fin > substr($c->hora_inicio, 0, 5)
                );

                $bloques->push([
                    'inicio' => $inicio,
                    'fin' => $fin,
                    'duracion' => $duracion,
                    'estado' => $pasado ? 'pasado' : ($ocupado ? 'ocupado' : 'libre'),
                ]);
            }
        }

        return $bloques;
    }

    /**
     * CU25 y CU35: registra una cita. La disponibilidad se revalida en el servidor,
     * dentro de una transacción que bloquea al médico (RN13), para que dos personas
     * no ocupen el mismo bloque.
     *
     * @param array{paciente_id: int, medico_id: int, especialidad_id: int, fecha: string, hora_inicio: string, motivo_consulta?: ?string} $datos
     */
    public function registrar(array $datos, ?User $por = null): Cita
    {
        try {
            return DB::transaction(function () use ($datos, $por) {
                $medico = Medico::query()->whereKey($datos['medico_id'])->lockForUpdate()->firstOrFail();

                $bloque = $this->bloqueDisponible($medico, $datos['fecha'], $datos['hora_inicio']);

                $cita = Cita::create([
                    'paciente_id' => $datos['paciente_id'],
                    'medico_id' => $medico->id,
                    'especialidad_id' => $datos['especialidad_id'],
                    'fecha' => $datos['fecha'],
                    'hora_inicio' => $bloque['inicio'],
                    'hora_fin' => $bloque['fin'],
                    'estado' => Cita::PROGRAMADA,
                    'motivo_consulta' => $datos['motivo_consulta'] ?? null,
                    'creado_por' => $por?->id,
                ]);

                Bitacora::registrar('crear', Cita::MODULO_BITACORA, $cita->id, $cita->etiquetaBitacora());

                return $cita;
            });
        } catch (QueryException $e) {
            $this->traducirChoque($e);
        }
    }

    /**
     * Devuelve el bloque libre que empieza a la hora indicada o lanza un error de validación
     * con el motivo (médico inactivo, fuera de horario, pasado u ocupado).
     *
     * @return array{inicio: string, fin: string, estado: string, duracion: int}
     */
    public function bloqueDisponible(Medico $medico, string $fecha, string $horaInicio, ?int $ignorarCitaId = null, string $campo = 'hora_inicio'): array
    {
        if (! $medico->activo) {
            throw ValidationException::withMessages(['medico_id' => 'El médico está inactivo y no recibe citas nuevas.']);
        }

        $bloque = $this->bloques($medico, $fecha, $ignorarCitaId)->firstWhere('inicio', $horaInicio);

        if (! $bloque) {
            throw ValidationException::withMessages([$campo => 'La hora elegida no corresponde a un bloque del horario de atención del médico ese día.']);
        }

        if ($bloque['estado'] === 'pasado') {
            throw ValidationException::withMessages([$campo => 'No se pueden programar citas en fechas u horas que ya pasaron.']);
        }

        if ($bloque['estado'] === 'ocupado') {
            throw ValidationException::withMessages([$campo => 'Ese horario ya está ocupado. Elija otro bloque libre del médico.']);
        }

        return $bloque;
    }

    /** Si PostgreSQL rechazó la cita por superposición (dos reservas simultáneas), se da el mismo mensaje. */
    public function traducirChoque(QueryException $e): never
    {
        if (str_contains($e->getMessage(), 'citas_sin_solape')) {
            throw ValidationException::withMessages(['hora_inicio' => 'Ese horario ya fue ocupado por otra cita. Elija otro bloque libre del médico.']);
        }

        throw $e;
    }

    private function aMinutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', $hora));

        return $h * 60 + $m;
    }

    private function aTexto(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
}
