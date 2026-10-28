<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerfilPacienteRequest;
use App\Services\PerfilPacienteService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CU33: registro público de la cuenta de paciente.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * La cuenta siempre se crea con rol Paciente (RN12): el rol no se toma del formulario,
     * así nadie puede elegirse un rol con más permisos. Se guarda también la ficha del paciente.
     */
    public function store(PerfilPacienteRequest $request, PerfilPacienteService $servicio): RedirectResponse
    {
        $user = $servicio->registrar($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
