<?php

namespace App\Http\Controllers;

use App\Http\Requests\EspecialidadRequest;
use App\Models\Especialidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Especialidades médicas (CU16 a CU19). No se eliminan: se desactivan.
 */
class EspecialidadController extends Controller
{
    /** CU17: lista las especialidades con su estado y cuántos médicos activos las atienden. */
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'todos');
        $texto = trim((string) $request->query('q'));

        $especialidades = Especialidad::query()
            ->withCount(['medicos as medicos_activos_count' => fn ($q) => $q->where('medicos.activo', true)])
            ->when($texto !== '', fn ($q) => $q->whereRaw('LOWER(nombre) LIKE ?', ['%' . mb_strtolower($texto) . '%']))
            ->when($estado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($estado === 'inactivos', fn ($q) => $q->where('activo', false))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('especialidades.index', compact('especialidades', 'estado'));
    }

    /** CU16 */
    public function create(): View
    {
        return view('especialidades.form', ['especialidad' => new Especialidad(['activo' => true])]);
    }

    /** CU16: el modelo limpia y normaliza el nombre antes de guardar. */
    public function store(EspecialidadRequest $request): RedirectResponse
    {
        Especialidad::create($request->validated() + ['activo' => true]);

        return redirect()->route('especialidades.index')->with('exito', 'Especialidad registrada correctamente.');
    }

    /** CU18 */
    public function edit(Especialidad $especialidad): View
    {
        return view('especialidades.form', compact('especialidad'));
    }

    /** CU18: la validación de unicidad excluye la propia especialidad. */
    public function update(EspecialidadRequest $request, Especialidad $especialidad): RedirectResponse
    {
        $especialidad->update($request->validated());

        return redirect()->route('especialidades.index')->with('exito', 'Especialidad actualizada.');
    }

    /** CU19: activa o desactiva; al desactivar se informa cuántos médicos activos la atienden. */
    public function cambiarEstado(Especialidad $especialidad): RedirectResponse
    {
        $especialidad->update(['activo' => ! $especialidad->activo]);

        if ($especialidad->activo) {
            return back()->with('exito', 'Especialidad activada.');
        }

        $afectados = $especialidad->medicos()->where('medicos.activo', true)->count();
        $aviso = $afectados > 0
            ? " Tiene {$afectados} médico(s) activo(s) asociado(s); conservan la especialidad pero no podrá asignarse a médicos nuevos."
            : '';

        return back()->with('exito', 'Especialidad desactivada.' . $aviso);
    }
}
