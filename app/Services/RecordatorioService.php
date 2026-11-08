<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\CorreoEnviado;
use App\Notifications\RecordatorioCita;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Recordatorios de citas (CU43): avisa al paciente el día antes de su cita.
 */
class RecordatorioService
{
    public function __construct(private CitaService $citas)
    {
    }

    /** Fecha de las citas que se recuerdan: mañana. */
    public function fechaObjetivo(): string
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->citas->hoy())->addDay()->toDateString();
    }

    /** Citas vigentes de mañana, con indicación de si ya se les envió el recordatorio. */
    public function citasDeManana(): Collection
    {
        return Cita::query()
            ->with(['paciente.user', 'medico', 'especialidad'])
            ->whereDate('fecha', $this->fechaObjetivo())
            ->whereIn('estado', [Cita::PROGRAMADA, Cita::CONFIRMADA])
            ->orderBy('hora_inicio')
            ->get();
    }

    /** ¿Por dónde se avisará? 'portal' (campanita + correo), 'correo' o null si no hay medio. */
    public function medio(Cita $cita): ?string
    {
        $p = $cita->paciente;

        if ($p->user) {
            return 'portal';
        }

        return $p->email ? 'correo' : null;
    }

    /** Envía el recordatorio de una cita. Devuelve false si el paciente no tiene ningún medio de contacto. */
    public function enviar(Cita $cita): bool
    {
        $medio = $this->medio($cita);

        if ($medio === null) {
            return false;
        }

        try {
            if ($medio === 'portal') {
                $cita->paciente->user->notify(new RecordatorioCita($cita));
            } else {
                Notification::route('mail', $cita->paciente->email)->notify(new RecordatorioCita($cita));
            }
        } catch (Throwable $e) {
            // La campanita (si hubo) ya quedó guardada; el correo falló: se deja constancia y se avisa.
            report($e);
            CorreoEnviado::registrarFallo(
                $cita->paciente->user?->email ?? (string) $cita->paciente->email,
                'Recordatorio de su cita en Clínica Santa Aurora',
                'Recordatorio de cita del ' . $cita->fecha->format('d/m/Y') . ' a las ' . substr((string) $cita->hora_inicio, 0, 5) . '.',
                $e,
            );

            throw $e;
        }

        $cita->forceFill(['recordatorio_enviado_at' => now()])->saveQuietly();
        Bitacora::registrar('recordatorio', Cita::MODULO_BITACORA, $cita->id, $cita->etiquetaBitacora() . " · por {$medio}");

        return true;
    }

    /**
     * Envía los recordatorios pendientes de las citas de mañana (una sola vez por cita).
     *
     * @return array{enviados: int, sin_medio: int, ya_enviados: int, fallidos: int}
     */
    public function enviarPendientes(): array
    {
        $r = ['enviados' => 0, 'sin_medio' => 0, 'ya_enviados' => 0, 'fallidos' => 0];

        foreach ($this->citasDeManana() as $cita) {
            if ($cita->recordatorio_enviado_at) {
                $r['ya_enviados']++;
            } else {
                try {
                    $this->enviar($cita) ? $r['enviados']++ : $r['sin_medio']++;
                } catch (Throwable) {
                    $r['fallidos']++;
                }
            }
        }

        return $r;
    }
}
