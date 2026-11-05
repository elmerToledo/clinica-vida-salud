<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Pago;
use App\Services\PagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * CU49: la recepcionista registra el cobro de una cita.
 */
class PagoController extends Controller
{
    public function store(Request $request, Cita $cita, PagoService $pagos): RedirectResponse
    {
        $datos = $request->validate([
            'metodo' => ['required', Rule::in(array_keys(Pago::METODOS))],
            'referencia' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-\s]+$/'],
        ], [
            'metodo.required' => 'Elija el método de pago.',
            'metodo.in' => 'El método de pago no es válido.',
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
}
