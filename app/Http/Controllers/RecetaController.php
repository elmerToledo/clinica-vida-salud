<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Services\RecetaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Receta médica: el médico la escribe, la publica para el paciente y la imprime;
 * el paciente ve e imprime solo las recetas ya publicadas de sus citas.
 */
class RecetaController extends Controller
{
    public function __construct(private readonly RecetaService $recetas)
    {
    }

    /** Médico: formulario para escribir o ver la receta de la cita. */
    public function editar(Request $request, Cita $cita): View
    {
        abort_unless($this->recetas->puedeRecetar($cita, $request->user()), 403);

        $cita->load(['paciente', 'medico', 'especialidad', 'receta']);

        return view('recetas.form', ['cita' => $cita, 'receta' => $cita->receta]);
    }

    /** Médico: guarda el borrador de la receta. */
    public function guardar(Request $request, Cita $cita): RedirectResponse
    {
        abort_unless($this->recetas->puedeRecetar($cita, $request->user()), 403);

        $datos = $request->validate([
            'diagnostico' => ['required', 'string', 'max:500'],
            'medicamentos' => ['required', 'string', 'max:2000'],
            'indicaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'diagnostico.required' => 'Escriba el diagnóstico.',
            'medicamentos.required' => 'Escriba los medicamentos y su dosis.',
            'diagnostico.max' => 'El diagnóstico admite hasta 500 caracteres.',
            'medicamentos.max' => 'Los medicamentos admiten hasta 2000 caracteres.',
            'indicaciones.max' => 'Las indicaciones admiten hasta 1000 caracteres.',
        ]);

        try {
            $receta = $this->recetas->guardar($cita, $datos, $request->user());
        } catch (ValidationException $e) {
            return redirect()->route('recetas.editar', $cita)->withErrors($e->errors())->withInput();
        }

        return redirect()->route('recetas.editar', $cita)->with('exito', "Receta {$receta->codigo} guardada. Aún no la ve el paciente: publíquela cuando esté lista.");
    }

    /** Médico: publica la receta para el paciente. */
    public function publicar(Request $request, Cita $cita): RedirectResponse
    {
        abort_unless($this->recetas->puedeRecetar($cita, $request->user()), 403);

        try {
            $receta = $this->recetas->publicar($cita);
        } catch (ValidationException $e) {
            return redirect()->route('recetas.editar', $cita)->withErrors($e->errors());
        }

        return redirect()->route('recetas.editar', $cita)->with('exito', "Receta {$receta->codigo} publicada: el paciente ya puede verla e imprimirla.");
    }

    /** Médico (su receta) o paciente (solo si está publicada): hoja imprimible. */
    public function imprimir(Request $request, Cita $cita): View
    {
        $usuario = $request->user();
        $receta = $cita->receta;
        abort_unless($receta, 404);

        $permitido = match ($usuario->rol) {
            'medico' => $usuario->medico?->id === $cita->medico_id,
            'paciente' => $receta->publicada && $usuario->paciente?->id === $cita->paciente_id,
            default => false,
        };
        abort_unless($permitido, 403);

        $cita->load(['paciente', 'medico', 'especialidad']);

        return view('recetas.imprimir', ['cita' => $cita, 'receta' => $receta]);
    }
}
