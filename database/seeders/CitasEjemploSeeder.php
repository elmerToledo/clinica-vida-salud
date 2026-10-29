<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use App\Services\CitaService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Citas de ejemplo para ver los reportes y la agenda con datos (no forma parte de la instalación normal).
 *
 * Uso: php artisan db:seed --class=CitasEjemploSeeder
 *
 * Usa los horarios de atención ya registrados: cada cita cae en un bloque válido de un médico activo.
 * Se puede ejecutar más de una vez: no repite un bloque que ya tiene cita.
 */
class CitasEjemploSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(20261028); // misma mezcla cada vez

        $hoy = CarbonImmutable::createFromFormat('Y-m-d', app(CitaService::class)->hoy(), CitaService::ZONA);
        $medicos = Medico::activos()
            ->with(['especialidades' => fn ($q) => $q->where('especialidades.activo', true), 'horarios' => fn ($q) => $q->where('activo', true)])
            ->get()
            ->filter(fn ($m) => $m->horarios->isNotEmpty() && $m->especialidades->isNotEmpty());
        $pacientes = Paciente::activos()->get();
        $recepcion = User::where('rol', 'recepcionista')->value('id');

        if ($medicos->isEmpty() || $pacientes->isEmpty()) {
            $this->command?->warn('Hacen falta médicos con horario y pacientes activos (ejecute antes ClinicaSeeder).');

            return;
        }

        $creadas = 0;

        // Desde tres semanas atrás hasta dos semanas adelante.
        for ($dia = $hoy->subDays(21); $dia <= $hoy->addDays(14); $dia = $dia->addDay()) {
            foreach ($medicos as $medico) {
                foreach ($medico->horarios->where('dia_semana', $dia->dayOfWeekIso) as $horario) {
                    $inicio = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $dia->toDateString() . ' ' . $horario->hora_inicio, CitaService::ZONA);
                    $fin = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $dia->toDateString() . ' ' . $horario->hora_fin, CitaService::ZONA);

                    for ($bloque = $inicio; $bloque->addMinutes($horario->duracion_cita_min) <= $fin; $bloque = $bloque->addMinutes($horario->duracion_cita_min)) {
                        if (mt_rand(1, 100) > 45) {
                            continue; // deja libres varios bloques
                        }

                        $creadas += $this->crear($medico, $pacientes, $bloque, $horario->duracion_cita_min, $hoy, $recepcion) ? 1 : 0;
                    }
                }
            }
        }

        $this->command?->info("Citas de ejemplo creadas: {$creadas}.");
    }

    private function crear(Medico $medico, $pacientes, CarbonImmutable $inicio, int $minutos, CarbonImmutable $hoy, ?int $recepcion): bool
    {
        $fecha = $inicio->toDateString();
        $hora = $inicio->format('H:i:s');

        if (Cita::where('medico_id', $medico->id)->where('fecha', $fecha)->where('hora_inicio', $hora)->exists()) {
            return false;
        }

        $estado = $this->estado($inicio->startOfDay()->lt($hoy) ? 'pasada' : ($fecha === $hoy->toDateString() ? 'hoy' : 'futura'));

        // Un paciente no puede tener dos citas a la misma hora (RF07).
        $paciente = $pacientes->shuffle()->first(fn ($p) => ! Cita::where('paciente_id', $p->id)
            ->where('fecha', $fecha)->where('hora_inicio', $hora)->where('estado', '!=', Cita::CANCELADA)->exists());
        if (! $paciente) {
            return false;
        }

        Cita::create([
            'paciente_id' => $paciente->id,
            'medico_id' => $medico->id,
            'especialidad_id' => $medico->especialidades->random()->id,
            'fecha' => $fecha,
            'hora_inicio' => $hora,
            'hora_fin' => $inicio->addMinutes($minutos)->format('H:i:s'),
            'estado' => $estado,
            'motivo_consulta' => ['Control general', 'Dolor de cabeza', 'Revisión de resultados', 'Consulta de rutina', 'Dolor de garganta'][mt_rand(0, 4)],
            'motivo_cancelacion' => $estado === Cita::CANCELADA ? 'Cancelada a pedido del paciente' : null,
            'llegada_at' => in_array($estado, [Cita::ATENDIDA], true) ? $inicio->subMinutes(10) : null,
            'creado_por' => $recepcion,
        ]);

        return true;
    }

    /** Estado según el momento de la cita: las pasadas ya tienen resultado y las futuras, no. */
    private function estado(string $momento): string
    {
        $n = mt_rand(1, 100);

        return match ($momento) {
            'pasada' => $n <= 60 ? Cita::ATENDIDA : ($n <= 80 ? Cita::CANCELADA : Cita::INASISTENCIA),
            'hoy' => $n <= 60 ? Cita::CONFIRMADA : ($n <= 90 ? Cita::PROGRAMADA : Cita::CANCELADA),
            default => $n <= 55 ? Cita::PROGRAMADA : ($n <= 90 ? Cita::CONFIRMADA : Cita::CANCELADA),
        };
    }
}
