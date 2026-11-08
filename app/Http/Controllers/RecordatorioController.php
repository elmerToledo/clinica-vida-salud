<?php

namespace App\Http\Controllers;

use App\Services\RecordatorioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Recordatorios de citas (CU43): recepción ve las citas de mañana y envía los avisos.
 * El mismo envío se hace solo cada día con el comando citas:recordatorios.
 */
class RecordatorioController extends Controller
{
    public function __construct(private RecordatorioService $recordatorios)
    {
    }

    public function index(): View
    {
        return view('recordatorios.index', [
            'fecha' => $this->recordatorios->fechaObjetivo(),
            'citas' => $this->recordatorios->citasDeManana(),
            'servicio' => $this->recordatorios,
        ]);
    }

    public function enviar(): RedirectResponse
    {
        $r = $this->recordatorios->enviarPendientes();

        $mensaje = "Recordatorios enviados: {$r['enviados']}.";
        if ($r['sin_medio'] > 0) {
            $mensaje .= " {$r['sin_medio']} paciente(s) no tienen cuenta ni correo registrado.";
        }
        if ($r['enviados'] === 0 && $r['sin_medio'] === 0 && $r['fallidos'] === 0) {
            $mensaje = $r['ya_enviados'] > 0 ? 'Todos los recordatorios de mañana ya fueron enviados.' : 'No hay citas para mañana.';
        }

        $respuesta = back();

        if ($r['fallidos'] > 0) {
            $respuesta->with('error', "No se pudo enviar el correo de {$r['fallidos']} recordatorio(s). Revise la configuración de correo e inténtelo de nuevo.");
            if ($r['enviados'] > 0 || $r['sin_medio'] > 0) {
                $respuesta->with('exito', $mensaje);
            }

            return $respuesta;
        }

        return $respuesta->with('exito', $mensaje);
    }
}
