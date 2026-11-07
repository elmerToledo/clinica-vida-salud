<?php

namespace App\Notifications;

use App\Models\Cita;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Recordatorio de una cita próxima (CU43).
 * Si el paciente tiene cuenta: campanita del portal + correo. Si no: solo correo a su email registrado.
 */
class RecordatorioCita extends Notification
{
    public function __construct(public Cita $cita)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $c = $this->cita;

        return (new MailMessage)
            ->subject('Recordatorio de su cita en Clínica Santa Aurora')
            ->greeting('Hola, ' . $c->paciente->nombres)
            ->line('Le recordamos que tiene una cita médica próxima:')
            ->line("Fecha: {$c->fecha->format('d/m/Y')} a las {$c->inicio}")
            ->line('Médico: ' . $c->medico->apellidos . ', ' . $c->medico->nombres)
            ->line('Especialidad: ' . $c->especialidad->nombre)
            ->line($c->ficha ? "Ficha de atención: N.° {$c->ficha}" : 'Presente su carnet de identidad al llegar.')
            ->line('Si no puede asistir, cancele o reprograme su cita con anticipación para liberar el horario.');
    }

    /** Contenido de la campanita. */
    public function toArray(object $notifiable): array
    {
        $c = $this->cita;

        return [
            'cita_id' => $c->id,
            'titulo' => 'Recordatorio de cita',
            'mensaje' => "Mañana {$c->fecha->format('d/m/Y')} a las {$c->inicio} tiene cita con {$c->medico->apellidos} ({$c->especialidad->nombre})"
                . ($c->ficha ? ", ficha N.° {$c->ficha}." : '.'),
        ];
    }
}
