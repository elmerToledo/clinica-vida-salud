<?php

use App\Models\Medico;
use App\Models\User;

// CU12 a CU15 (RF03, RN02, RN04, RN11, CU30 permiso).

function datosMedico(array $especialidades, array $extra = []): array
{
    return array_merge([
        'registro_profesional' => 'RP-2001',
        'nombres' => 'Luis',
        'apellidos' => 'Mendoza',
        'telefono' => '70020002',
        'email' => 'luis@example.com',
        'especialidades' => array_map(fn ($e) => $e->id, $especialidades),
    ], $extra);
}

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->recepcion = User::factory()->recepcionista()->create();
    $this->pediatria = crearEspecialidad('Pediatria');
    $this->general = crearEspecialidad('Medicina general');
});

it('registra un médico con sus especialidades y código único (CU12)', function () {
    $this->actingAs($this->admin)->post('/medicos', datosMedico([$this->pediatria, $this->general]))
        ->assertRedirect(route('medicos.index'));

    $medico = Medico::where('registro_profesional', 'RP-2001')->firstOrFail();

    expect($medico->activo)->toBeTrue()
        ->and($medico->puede_marcar_atendida)->toBeFalse()
        ->and($medico->codigo)->toStartWith('MED-')
        ->and($medico->especialidades()->count())->toBe(2);
});

it('rechaza un registro profesional repetido (RN02)', function () {
    crearMedico(['registro_profesional' => 'RP-2001'], [$this->general]);

    $this->actingAs($this->admin)->post('/medicos', datosMedico([$this->general]))
        ->assertSessionHasErrors('registro_profesional');

    expect(Medico::count())->toBe(1);
});

it('exige al menos una especialidad', function () {
    $this->actingAs($this->admin)->post('/medicos', datosMedico([], ['especialidades' => []]))
        ->assertSessionHasErrors('especialidades');

    $this->actingAs($this->admin)->post('/medicos', collect(datosMedico([]))->except('especialidades')->all())
        ->assertSessionHasErrors('especialidades');
});

it('no asocia especialidades inactivas ni inexistentes', function () {
    $inactiva = crearEspecialidad('Cardiología', false);

    $this->actingAs($this->admin)->post('/medicos', datosMedico([$inactiva]))
        ->assertSessionHasErrors('especialidades.0');

    $this->actingAs($this->admin)->post('/medicos', datosMedico([], ['especialidades' => [9999]]))
        ->assertSessionHasErrors('especialidades.0');
});

it('rechaza los campos obligatorios vacíos y formatos inválidos', function () {
    $this->actingAs($this->admin)->post('/medicos', [])
        ->assertSessionHasErrors(['registro_profesional', 'nombres', 'apellidos', 'especialidades']);

    $this->actingAs($this->admin)->post('/medicos', datosMedico([$this->general], ['email' => 'mal', 'telefono' => 'xx']))
        ->assertSessionHasErrors(['email', 'telefono']);
});

it('asocia una cuenta de usuario de rol médico y rechaza cuentas de otro rol', function () {
    $cuenta = User::factory()->medico()->create();
    $this->actingAs($this->admin)->post('/medicos', datosMedico([$this->general], ['user_id' => $cuenta->id]))
        ->assertSessionHasNoErrors();
    expect(Medico::first()->user_id)->toBe($cuenta->id);

    $paciente = User::factory()->paciente()->create();
    $this->actingAs($this->admin)->post('/medicos', datosMedico([$this->general], ['registro_profesional' => 'RP-3000', 'user_id' => $paciente->id]))
        ->assertSessionHasErrors('user_id');
});

it('no permite usar la misma cuenta para dos médicos', function () {
    $cuenta = User::factory()->medico()->create();
    crearMedico(['user_id' => $cuenta->id], [$this->general]);

    $this->actingAs($this->admin)->post('/medicos', datosMedico([$this->general], ['user_id' => $cuenta->id]))
        ->assertSessionHasErrors('user_id');
});

it('guarda el permiso para marcar citas como atendidas (CU30)', function () {
    $this->actingAs($this->admin)->post('/medicos', datosMedico([$this->general], ['puede_marcar_atendida' => '1']));

    expect(Medico::first()->puede_marcar_atendida)->toBeTrue();
});

it('modifica datos y especialidades (CU14)', function () {
    $medico = crearMedico(['registro_profesional' => 'RP-2001'], [$this->general]);

    $this->actingAs($this->admin)
        ->put(route('medicos.update', $medico), datosMedico([$this->pediatria], ['nombres' => 'Luis Alberto']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('medicos.index'));

    $medico->refresh();
    expect($medico->nombres)->toBe('Luis Alberto')
        ->and($medico->especialidades()->pluck('especialidades.id')->all())->toBe([$this->pediatria->id]);
});

it('busca por nombre, por especialidad y por filtro de especialidad (CU13)', function () {
    crearMedico(['apellidos' => 'Quiroga'], [$this->pediatria]);
    crearMedico(['apellidos' => 'Salvatierra'], [$this->general]);

    $this->actingAs($this->recepcion)->get('/medicos?q=quiroga')
        ->assertOk()->assertSee('Quiroga')->assertDontSee('Salvatierra');

    $this->actingAs($this->recepcion)->get('/medicos?q=pediatria')
        ->assertOk()->assertSee('Quiroga')->assertDontSee('Salvatierra');

    $this->actingAs($this->recepcion)->get('/medicos?especialidad=' . $this->general->id)
        ->assertOk()->assertSee('Salvatierra')->assertDontSee('Quiroga');
});

it('desactiva sin eliminar (CU15, RN04, RN11)', function () {
    $medico = crearMedico([], [$this->general]);

    $this->actingAs($this->admin)->patch(route('medicos.estado', $medico))->assertSessionHas('exito');

    $this->assertDatabaseHas('medicos', ['id' => $medico->id, 'activo' => false]);
});

it('la recepcionista consulta pero no administra médicos', function () {
    $medico = crearMedico([], [$this->general]);

    $this->actingAs($this->recepcion)->get('/medicos')->assertOk();
    $this->actingAs($this->recepcion)->post('/medicos', datosMedico([$this->general]))->assertForbidden();
    $this->actingAs($this->recepcion)->patch(route('medicos.estado', $medico))->assertForbidden();
});
