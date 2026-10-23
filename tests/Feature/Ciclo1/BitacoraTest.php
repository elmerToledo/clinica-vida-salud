<?php

use App\Models\Bitacora;
use App\Models\User;

// Bitácora del sistema: se registra sola y solo la consulta el administrador.

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create(['usuario' => 'admin1']);
});

it('registra el ingreso y la salida de sesión', function () {
    $usuario = User::factory()->recepcionista()->create(['usuario' => 'rosa', 'password' => 'Clave12345']);

    $this->post('/login', ['email' => 'rosa', 'password' => 'Clave12345']);
    $this->post('/logout');

    expect(Bitacora::where('accion', 'ingreso')->where('user_id', $usuario->id)->exists())->toBeTrue()
        ->and(Bitacora::where('accion', 'salida')->where('user_id', $usuario->id)->exists())->toBeTrue();
});

it('registra un ingreso fallido sin guardar la contraseña', function () {
    User::factory()->create(['usuario' => 'rosa', 'password' => 'Clave12345']);

    $this->post('/login', ['email' => 'rosa', 'password' => 'clave-equivocada']);

    $registro = Bitacora::where('accion', 'ingreso_fallido')->firstOrFail();

    expect($registro->usuario)->toBe('rosa')
        ->and($registro->detalle)->not->toContain('clave-equivocada');
});

it('registra quién crea, modifica y desactiva un paciente', function () {
    $this->actingAs($this->admin)->post('/pacientes', [
        'ci' => '7001001', 'nombres' => 'Ana', 'apellidos' => 'Rojas', 'fecha_nacimiento' => '1990-05-10',
        'sexo' => 'F', 'telefono' => '71110000',
    ]);

    $paciente = \App\Models\Paciente::where('ci', '7001001')->firstOrFail();

    $crear = Bitacora::where('modulo', 'pacientes')->where('accion', 'crear')->firstOrFail();
    expect($crear->usuario)->toBe('admin1')->and($crear->registro_id)->toBe($paciente->id);

    $this->actingAs($this->admin)->patch(route('pacientes.estado', $paciente));

    expect(Bitacora::where('modulo', 'pacientes')->where('accion', 'desactivar')->where('registro_id', $paciente->id)->exists())->toBeTrue();
});

it('al modificar anota los campos cambiados pero no sus valores ni contraseñas', function () {
    $usuario = User::factory()->recepcionista()->create();
    Bitacora::query()->delete();

    $usuario->update(['password' => 'ClaveNueva123', 'name' => 'Nombre Nuevo']);

    $registro = Bitacora::where('modulo', 'usuarios')->where('accion', 'modificar')->firstOrFail();

    expect($registro->detalle)->toContain('contraseña')->toContain('name')
        ->and($registro->detalle)->not->toContain('ClaveNueva123');
});

it('solo el administrador consulta la bitácora', function () {
    $this->get('/bitacora')->assertRedirect('/');

    foreach (['recepcionista', 'medico', 'paciente'] as $rol) {
        $this->actingAs(User::factory()->{$rol}()->create())->get('/bitacora')->assertForbidden();
    }

    $this->actingAs($this->admin)->get('/bitacora')->assertOk()->assertSee('Bitácora del');
});

it('filtra por módulo y por texto', function () {
    Bitacora::registrar('crear', 'pacientes', 1, 'Paciente Zambrana', $this->admin);
    Bitacora::registrar('crear', 'medicos', 2, 'Médico Quiroga', $this->admin);

    $this->actingAs($this->admin)->get('/bitacora?modulo=medicos')
        ->assertOk()->assertSee('Quiroga')->assertDontSee('Zambrana');

    $this->actingAs($this->admin)->get('/bitacora?q=zambrana')
        ->assertOk()->assertSee('Zambrana')->assertDontSee('Quiroga');
});

it('aparece en el menú solo del administrador', function () {
    $this->actingAs($this->admin)->get('/dashboard')->assertSee('Bitácora');
    $this->actingAs(User::factory()->recepcionista()->create())->get('/dashboard')->assertDontSee('Bitácora');
});
