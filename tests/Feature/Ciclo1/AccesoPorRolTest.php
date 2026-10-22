<?php

use App\Models\User;

// RN12: cada operación exige sesión válida y rol autorizado.

it('redirige al login a quien no inició sesión', function (string $ruta) {
    $this->get($ruta)->assertRedirect('/login');
})->with(['/dashboard', '/usuarios', '/pacientes', '/medicos', '/especialidades', '/horarios']);

it('muestra el inicio a cada rol', function (string $rol) {
    $usuario = User::factory()->{$rol}()->create();

    $this->actingAs($usuario)->get('/dashboard')->assertOk();
})->with(['administrador', 'recepcionista', 'medico', 'paciente']);

it('niega a un paciente los módulos del sistema', function (string $ruta) {
    $this->actingAs(User::factory()->paciente()->create())->get($ruta)->assertForbidden();
})->with(['/usuarios', '/pacientes', '/medicos', '/especialidades', '/horarios']);

it('permite a la recepcionista consultar pero no administrar', function () {
    $recepcion = User::factory()->recepcionista()->create();

    foreach (['/pacientes', '/pacientes/create', '/medicos', '/especialidades', '/horarios'] as $ruta) {
        $this->actingAs($recepcion)->get($ruta)->assertOk();
    }

    foreach (['/usuarios', '/medicos/create', '/especialidades/create', '/horarios/create'] as $ruta) {
        $this->actingAs($recepcion)->get($ruta)->assertForbidden();
    }
});

it('permite al médico consultar solo horarios', function () {
    $medico = User::factory()->medico()->create();

    $this->actingAs($medico)->get('/horarios')->assertOk();

    foreach (['/pacientes', '/medicos', '/especialidades', '/usuarios', '/horarios/create'] as $ruta) {
        $this->actingAs($medico)->get($ruta)->assertForbidden();
    }
});

it('permite al administrador entrar a todos los módulos', function () {
    $admin = User::factory()->administrador()->create();

    $rutas = [
        '/usuarios', '/usuarios/create',
        '/pacientes', '/pacientes/create',
        '/medicos', '/medicos/create',
        '/especialidades', '/especialidades/create',
        '/horarios', '/horarios/create',
        '/profile',
    ];

    foreach ($rutas as $ruta) {
        $this->actingAs($admin)->get($ruta)->assertOk();
    }
});

it('expulsa a una cuenta desactivada que tenía la sesión abierta', function () {
    $inactivo = User::factory()->administrador()->inactivo()->create();

    $this->actingAs($inactivo)->get('/usuarios')->assertRedirect('/login');
    $this->assertGuest();
});
