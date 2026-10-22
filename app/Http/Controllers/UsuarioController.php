<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Usuarios y roles (CU03 a CU06). Solo el Administrador accede (RN12).
 * Las contraseñas se guardan con hash y nunca se muestran.
 */
class UsuarioController extends Controller
{
    /** CU04: listado con búsqueda y filtros por rol y estado. */
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'todos');
        $rol = (string) $request->query('rol');

        $usuarios = User::query()
            ->buscar($request->query('q'))
            ->when(array_key_exists($rol, User::ROLES), fn ($q) => $q->where('rol', $rol))
            ->when($estado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($estado === 'inactivos', fn ($q) => $q->where('activo', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('usuarios.index', compact('usuarios', 'estado', 'rol'));
    }

    /** CU03 */
    public function create(): View
    {
        return view('usuarios.form', ['usuario' => new User(['rol' => 'recepcionista', 'activo' => true])]);
    }

    /** CU03: crea la cuenta con la contraseña cifrada (cast "hashed" del modelo). */
    public function store(UsuarioRequest $request): RedirectResponse
    {
        $usuario = new User($request->safe()->except('activo'));
        $usuario->activo = $request->boolean('activo');
        $usuario->email_verified_at = now();
        $usuario->save();

        return redirect()->route('usuarios.index')->with('exito', 'Usuario registrado correctamente.');
    }

    /** CU05 */
    public function edit(User $usuario): View
    {
        return view('usuarios.form', compact('usuario'));
    }

    /** CU05: modifica datos y rol; la contraseña solo cambia si se escribe una nueva. */
    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->safe()->except('activo');
        $activo = $request->boolean('activo');

        // El administrador no puede quitarse su propio rol ni desactivarse: quedaría sin acceso.
        if ($usuario->is($request->user()) && ($datos['rol'] !== $usuario->rol || ! $activo)) {
            return back()->withInput()->withErrors(['rol' => 'No puede cambiar su propio rol ni desactivar su propia cuenta.']);
        }

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->fill($datos);
        $usuario->activo = $activo;
        $usuario->save();

        return redirect()->route('usuarios.index')->with('exito', 'Usuario actualizado.');
    }

    /** CU06: activa o desactiva; no modifica la contraseña ni el rol. */
    public function cambiarEstado(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puede desactivar su propia cuenta.');
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        return back()->with('exito', $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado: ya no podrá iniciar sesión.');
    }
}
