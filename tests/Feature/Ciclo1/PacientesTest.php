<?php

use App\Models\Paciente;
use App\Models\User;

// CU08 a CU11 (RF02, RN01, RN11).

function datosPaciente(array $extra = []): array
{
    return array_merge([
        'ci' => '1234567',
        'nombres' => 'María',
        'apellidos' => 'López',
        'fecha_nacimiento' => '1990-05-14',
        'sexo' => 'F',
        'telefono' => '71110001',
        'email' => 'maria@example.com',
        'direccion' => 'Av. Principal 123',
    ], $extra);
}

beforeEach(function () {
    $this->recepcion = User::factory()->recepcionista()->create();
    $this->admin = User::factory()->administrador()->create();
});

it('registra un paciente activo con código único (CU08)', function () {
    $this->actingAs($this->recepcion)->post('/pacientes', datosPaciente())
        ->assertRedirect(route('pacientes.index'))
        ->assertSessionHas('exito');

    $paciente = Paciente::where('ci', '1234567')->firstOrFail();

    expect($paciente->activo)->toBeTrue()
        ->and($paciente->codigo)->toStartWith('PAC-');
});

it('rechaza dos pacientes con la misma CI (RF02)', function () {
    crearPaciente(['ci' => '1234567']);

    $this->actingAs($this->recepcion)->post('/pacientes', datosPaciente())
        ->assertSessionHasErrors('ci');

    expect(Paciente::count())->toBe(1);
});

it('rechaza una fecha de nacimiento futura', function () {
    $this->actingAs($this->recepcion)
        ->post('/pacientes', datosPaciente(['fecha_nacimiento' => now()->addDay()->format('Y-m-d')]))
        ->assertSessionHasErrors('fecha_nacimiento');

    expect(Paciente::count())->toBe(0);
});

it('rechaza los campos obligatorios vacíos', function () {
    $this->actingAs($this->recepcion)->post('/pacientes', [])
        ->assertSessionHasErrors(['ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'telefono']);
});

it('rechaza formatos inválidos', function () {
    $this->actingAs($this->recepcion)->post('/pacientes', datosPaciente([
        'nombres' => 'Mar1a<script>',
        'telefono' => 'abc',
        'email' => 'no-es-correo',
        'sexo' => 'X',
    ]))->assertSessionHasErrors(['nombres', 'telefono', 'email', 'sexo']);
});

it('modifica un paciente conservando su propia CI (CU10)', function () {
    $paciente = crearPaciente(['ci' => '1234567']);

    $this->actingAs($this->recepcion)
        ->put(route('pacientes.update', $paciente), datosPaciente(['telefono' => '72220002']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('pacientes.index'));

    expect($paciente->fresh()->telefono)->toBe('72220002');
});

it('no permite modificar usando la CI de otro paciente', function () {
    crearPaciente(['ci' => '9999999']);
    $paciente = crearPaciente(['ci' => '1234567']);

    $this->actingAs($this->recepcion)
        ->put(route('pacientes.update', $paciente), datosPaciente(['ci' => '9999999']))
        ->assertSessionHasErrors('ci');

    expect($paciente->fresh()->ci)->toBe('1234567');
});

it('busca pacientes por CI, nombres o apellidos (CU09)', function () {
    crearPaciente(['ci' => '2345678', 'apellidos' => 'Perez']);
    crearPaciente(['ci' => '3456789', 'apellidos' => 'Gomez']);

    $this->actingAs($this->recepcion)->get('/pacientes?q=perez')
        ->assertOk()->assertSee('Perez')->assertDontSee('Gomez');

    $this->actingAs($this->recepcion)->get('/pacientes?q=3456789')
        ->assertOk()->assertSee('Gomez')->assertDontSee('Perez');
});

it('filtra pacientes por estado', function () {
    crearPaciente(['apellidos' => 'Activo']);
    crearPaciente(['apellidos' => 'Retirado', 'activo' => false]);

    $this->actingAs($this->recepcion)->get('/pacientes?estado=inactivos')
        ->assertOk()->assertSee('Retirado')->assertDontSee('Activo,');
});

it('solo el administrador activa o desactiva pacientes (CU11)', function () {
    $paciente = crearPaciente();

    $this->actingAs($this->recepcion)->patch(route('pacientes.estado', $paciente))->assertForbidden();
    expect($paciente->fresh()->activo)->toBeTrue();

    $this->actingAs($this->admin)->patch(route('pacientes.estado', $paciente))->assertSessionHas('exito');
    expect($paciente->fresh()->activo)->toBeFalse();

    $this->actingAs($this->admin)->patch(route('pacientes.estado', $paciente));
    expect($paciente->fresh()->activo)->toBeTrue();
});

it('conserva el registro al desactivar (RN11)', function () {
    $paciente = crearPaciente();

    $this->actingAs($this->admin)->patch(route('pacientes.estado', $paciente));

    $this->assertDatabaseHas('pacientes', ['id' => $paciente->id, 'activo' => false]);
});
