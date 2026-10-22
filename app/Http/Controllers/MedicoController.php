<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicoRequest;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Médicos (CU12 a CU15). Un médico inactivo no recibe citas nuevas pero se conserva su historial (RN04, RN11).
 */
class MedicoController extends Controller
{
    /** CU13: listado con búsqueda por nombre o especialidad. */
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'todos');
        $especialidadId = $request->query('especialidad');

        $medicos = Medico::query()
            ->with('especialidades')
            ->buscar($request->query('q'))
            ->when($especialidadId, fn ($q) => $q->whereHas('especialidades', fn ($e) => $e->where('especialidades.id', $especialidadId)))
            ->when($estado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($estado === 'inactivos', fn ($q) => $q->where('activo', false))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10)
            ->withQueryString();

        return view('medicos.index', [
            'medicos' => $medicos,
            'especialidades' => Especialidad::orderBy('nombre')->get(),
            'estado' => $estado,
            'especialidadId' => $especialidadId,
        ]);
    }

    /** CU12 */
    public function create(): View
    {
        return view('medicos.form', $this->datosFormulario(new Medico(['activo' => true])));
    }

    /** CU12: guarda el médico y sus especialidades en una sola transacción. */
    public function store(MedicoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $datos = $request->safe()->except('especialidades');
            $datos['puede_marcar_atendida'] = $request->boolean('puede_marcar_atendida');
            $datos['activo'] = true;

            $medico = Medico::create($datos);
            $medico->especialidades()->sync($request->validated('especialidades'));
        });

        return redirect()->route('medicos.index')->with('exito', 'Médico registrado correctamente.');
    }

    /** CU14 */
    public function edit(Medico $medico): View
    {
        return view('medicos.form', $this->datosFormulario($medico));
    }

    /** CU14: revalida la unicidad del registro profesional y las especialidades asociadas. */
    public function update(MedicoRequest $request, Medico $medico): RedirectResponse
    {
        DB::transaction(function () use ($request, $medico) {
            $datos = $request->safe()->except('especialidades');
            $datos['puede_marcar_atendida'] = $request->boolean('puede_marcar_atendida');

            $medico->update($datos);
            $medico->especialidades()->sync($request->validated('especialidades'));
        });

        return redirect()->route('medicos.index')->with('exito', 'Datos del médico actualizados.');
    }

    /** CU15: activa o desactiva sin eliminar. */
    public function cambiarEstado(Medico $medico): RedirectResponse
    {
        $medico->update(['activo' => ! $medico->activo]);

        return back()->with('exito', $medico->activo ? 'Médico activado.' : 'Médico desactivado: ya no recibirá horarios ni citas nuevas.');
    }

    /** Datos que necesita el formulario: especialidades elegibles y cuentas de médico libres. */
    private function datosFormulario(Medico $medico): array
    {
        $seleccionadas = $medico->exists ? $medico->especialidades()->pluck('especialidades.id')->all() : [];

        return [
            'medico' => $medico,
            // Activas, más las que el médico ya tenía aunque estén inactivas.
            'especialidades' => Especialidad::where('activo', true)->orWhereIn('id', $seleccionadas)->orderBy('nombre')->get(),
            'seleccionadas' => $seleccionadas,
            // Cuentas de rol médico activas que aún no están ligadas a otro médico (o la del propio médico).
            'cuentas' => User::query()
                ->where('rol', 'medico')
                ->where('activo', true)
                ->where(function ($q) use ($medico) {
                    $q->whereDoesntHave('medico')->when($medico->user_id, fn ($q2) => $q2->orWhere('id', $medico->user_id));
                })
                ->orderBy('name')
                ->get(),
        ];
    }
}
