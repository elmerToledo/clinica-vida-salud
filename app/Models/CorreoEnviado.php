<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Events\MessageSent;
use Throwable;

/**
 * Constancia de correos: una fila por cada correo que el sistema envía (o intenta enviar).
 */
class CorreoEnviado extends Model
{
    protected $table = 'correos_enviados';

    public const ENVIADO = 'enviado';

    public const SIMULADO = 'simulado';

    public const FALLIDO = 'fallido';

    protected $fillable = ['destinatario', 'asunto', 'contenido', 'estado', 'error'];

    /** ¿El correo sale de verdad (SMTP, etc.) o solo se simula (log/array)? */
    public static function modoSimulado(): bool
    {
        return in_array(config('mail.default'), ['log', 'array'], true);
    }

    public static function modoActual(): string
    {
        return self::modoSimulado() ? 'Simulado (MAIL_MAILER=' . config('mail.default') . ')' : 'Real (MAIL_MAILER=' . config('mail.default') . ')';
    }

    /** Registra un correo que el sistema acaba de enviar. Nunca lanza errores. */
    public static function registrarEnvio(MessageSent $evento): void
    {
        try {
            $mensaje = $evento->message;
            $original = method_exists($evento->sent, 'getOriginalMessage') ? $evento->sent->getOriginalMessage() : null;
            $destinatarios = collect($evento->sent->getEnvelope()->getRecipients())->map(fn ($a) => $a->getAddress())->implode(', ');
            if ($destinatarios === '') {
                $destinatarios = collect($original?->getTo() ?? [])->map(fn ($a) => $a->getAddress())->implode(', ');
            }
            $texto = self::aTexto($original?->getTextBody());
            if ($texto === '') {
                $html = self::aTexto($original?->getHtmlBody());
                $html = preg_replace('#<(style|head|script)\b.*?</\1>#si', '', $html);
                $texto = trim(html_entity_decode(strip_tags((string) preg_replace('#<br\s*/?>|</p>|</tr>|</h\d>#i', "\n", (string) $html))));
                $texto = (string) preg_replace("/\n\s*\n+/", "\n\n", $texto);
            }
            if ($texto === '') {
                // Último recurso: el mensaje completo ya codificado, para que la constancia nunca quede vacía.
                $texto = $evento->sent->getMessage()->toString();
            }

            self::create([
                'destinatario' => $destinatarios !== '' ? $destinatarios : '(sin destinatario)',
                'asunto' => (string) ($original?->getSubject() ?? $mensaje->getSubject()),
                'contenido' => trim((string) $texto),
                'estado' => self::modoSimulado() ? self::SIMULADO : self::ENVIADO,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** El cuerpo de Symfony puede ser texto o un flujo; lo devuelve siempre como texto. */
    private static function aTexto(mixed $cuerpo): string
    {
        if (is_resource($cuerpo)) {
            rewind($cuerpo);

            return (string) stream_get_contents($cuerpo);
        }

        return trim((string) $cuerpo);
    }

    /** Registra un correo que no se pudo enviar. Nunca lanza errores. */
    public static function registrarFallo(string $destinatario, string $asunto, string $contenido, Throwable $causa): void
    {
        try {
            self::create([
                'destinatario' => $destinatario,
                'asunto' => $asunto,
                'contenido' => $contenido,
                'estado' => self::FALLIDO,
                'error' => mb_substr($causa->getMessage(), 0, 500),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
