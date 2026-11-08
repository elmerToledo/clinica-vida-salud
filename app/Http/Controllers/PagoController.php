<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Pago;
use App\Services\PagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * CU49: cobro de una cita. La recepcionista registra efectivo o tarjeta en ventanilla;
 * el paciente paga en línea con PayPal (simulado, sin cobro real).
 */
class PagoController extends Controller
{
    public function store(Request $request, Cita $cita, PagoService $pagos): RedirectResponse
    {
        $datos = $request->validate([
            'metodo' => ['required', Rule::in(array_keys(Pago::METODOS_RECEPCION))],
            'referencia' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-\s]+$/'],
        ], [
            'metodo.required' => 'Elija el método de pago.',
            'metodo.in' => 'El método de pago no es válido. El pago con PayPal lo realiza el paciente en línea.',
            'referencia.regex' => 'La referencia solo admite letras, números y guiones.',
        ]);

        try {
            $pago = $pagos->registrar($cita, $datos, $request->user());
        } catch (ValidationException $e) {
            return redirect()->route('citas.show', $cita)->withErrors($e->errors())->withInput();
        }

        return redirect()->route('citas.show', $cita)
            ->with('exito', "Pago {$pago->codigo} registrado: Bs {$pago->monto} con {$pago->metodo_nombre}.");
    }

    /** Paciente: pantalla de pago con PayPal (simulada). */
    public function pagar(Request $request, Cita $cita, PagoService $pagos): View|RedirectResponse
    {
        $this->autorizarPaciente($request, $cita);

        if (! $pagos->puedeCobrarse($cita)) {
            return redirect()->route('citas.show', $cita)->withErrors(['metodo' => 'Esta cita no admite pago: ya fue pagada o no está vigente.']);
        }

        $cita->load(['especialidad', 'medico']);

        return view('citas.pagar', ['cita' => $cita]);
    }

    /** Paciente: confirma el pago con PayPal. Genera el número de operación y registra el pago. */
    public function pagarEnLinea(Request $request, Cita $cita, PagoService $pagos): RedirectResponse
    {
        $this->autorizarPaciente($request, $cita);

        try {
            $pago = $pagos->pagarEnLinea($cita, $request->user());
        } catch (ValidationException $e) {
            return redirect()->route('citas.show', $cita)->withErrors($e->errors());
        }

        return redirect()->route('citas.show', $cita)
            ->with('exito', "Pago {$pago->codigo} realizado con PayPal: Bs {$pago->monto}. Operación {$pago->referencia}.");
    }

    private function autorizarPaciente(Request $request, Cita $cita): void
    {
        abort_unless($request->user()->paciente?->id === $cita->paciente_id, 403);
    }
}
