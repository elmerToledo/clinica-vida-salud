<?php

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\User;
use App\Services\CitaService;
use Carbon\CarbonImmutable;

// CU24, CU25 y CU31 (RF05, RF06, RF07, RN03 a RN09, RN13).

beforeEach(function () {
    $this->recepcion = User::factory()->recepcionista()->create();
    $this->especialidad = crearEspecialidad('Cardiología');
    $this->medico = crearMedico(['nombres' => 'Alberto'], [$this->especialidad]);
    crearHorario($this->medico, 1, '08:00', '12:00', 30);
    $this->paciente = crearPaciente();
});

it('calcula los bloques libres según el horario y la duración (CU24)', function () {
    $bloques = app(CitaService::class)->bloques($this->medico, proximoLunes());

    expect($bloques)->toHaveCount(8)
        ->and($bloques->first())->toMatchArray(['inicio' => '08:00', 'fin' => '08:30', 'estado' => 'libre'])
        ->and($bloques->last())->toMatchArray(['inicio' => '11:30', 'fin' => '12:00']);
});

it('no ofrece bloques un día sin horario ni a un médico inactivo (CU24, RN04)', function () {
    $martes = CarbonImmutable::parse(proximoLunes())->addDay()->toDateString();
    expect(app(CitaService::class)->bloques($this->medico, $martes))->toBeEmpty();

    $this->medico->update(['activo' => false]);
    expect(app(CitaService::class)->bloques($this->medico->fresh(), proximoLunes()))->toBeEmpty();
});

it('marca ocupado el bloque con cita y libre el de una cita cancelada (CU24, RN09)', function () {
    $cita = crearCita($this);
    $servicio = app(CitaService::class);

    expect($servicio->bloques($this->medico, proximoLunes())->firstWhere('inicio', '09:00')['estado'])->toBe('ocupado');

    $cita->update(['estado' => 'cancelada']);

    expect($servicio->bloques($this->medico, proximoLunes())->firstWhere('inicio', '09:00')['estado'])->toBe('libre');
});

it('marca como pasados los bloques que ya empezaron hoy (CU24, RN06)', function () {
    $lunes = proximoLunes();
    $this->travelTo(CarbonImmutable::createFromFormat('Y-m-d H:i', "$lunes 10:05", CitaService::ZONA));

    $bloques = app(CitaService::class)->bloques($this->medico, $lunes);

    expect($bloques->firstWhere('inicio', '10:00')['estado'])->toBe('pasado')
        ->and($bloques->firstWhere('inicio', '10:30')['estado'])->toBe('libre');
});

it('muestra la disponibilidad a la recepcionista', function () {
    $this->actingAs($this->recepcion)
        ->get('/citas/disponibilidad?medico=' . $this->medico->id . '&fecha=' . proximoLunes())
        ->assertOk()
        ->assertSee('08:00')
        ->assertSee('Reservar');
});

it('registra una cita válida con código, hora final y bitácora (CU25)', function () {
    $this->actingAs($this->recepcion)->post('/citas', datosCita($this))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $cita = Cita::first();
    expect($cita->codigo)->toBe('CIT-00001')
        ->and($cita->estado)->toBe('programada')
        ->and($cita->inicio)->toBe('09:00')
        ->and($cita->fin)->toBe('09:30')
        ->and($cita->creado_por)->toBe($this->recepcion->id);

    expect(Bitacora::where('modulo', 'citas')->where('accion', 'crear')->where('registro_id', $cita->id)->exists())->toBeTrue();
});

it('rechaza un bloque ya ocupado por otra cita (RN07)', function () {
    crearCita($this);

    $this->actingAs($this->recepcion)->post('/citas', datosCita($this, ['paciente_id' => crearPaciente()->id]))
        ->assertSessionHasErrors('hora_inicio');

    expect(Cita::count())->toBe(1);
});

it('permite reservar un bloque cuya cita fue cancelada (RN09)', function () {
    crearCita($this, ['estado' => 'cancelada']);

    $this->actingAs($this->recepcion)->post('/citas', datosCita($this))
        ->assertSessionHasNoErrors();

    expect(Cita::count())->toBe(2);
});

it('rechaza horas fuera del horario o que no empiezan en un bloque (RN05, RN08)', function (string $hora) {
    $this->actingAs($this->recepcion)->post('/citas', datosCita($this, ['hora_inicio' => $hora]))
        ->assertSessionHasErrors('hora_inicio');

    expect(Cita::count())->toBe(0);
})->with(['07:30', '08:15', '12:00', '15:00']);

it('rechaza un día en que el médico no atiende', function () {
    $martes = CarbonImmutable::parse(proximoLunes())->addDay()->toDateString();

    $this->actingAs($this->recepcion)->post('/citas', datosCita($this, ['fecha' => $martes]))
        ->assertSessionHasErrors('hora_inicio');
});

it('rechaza fechas y horas que ya pasaron (RN06)', function () {
    $lunes = proximoLunes();
    $this->travelTo(CarbonImmutable::createFromFormat('Y-m-d H:i', "$lunes 10:05", CitaService::ZONA));

    $this->actingAs($this->recepcion)->post('/citas', datosCita($this, ['fecha' => $lunes, 'hora_inicio' => '09:00']))
        ->assertSessionHasErrors('hora_inicio');

    $ayer = CarbonImmutable::parse($lunes)->subDay()->toDateString();
    $this->actingAs($this->recepcion)->post('/citas', datosCita($this, ['fecha' => $ayer]))
        ->assertSessionHasErrors('hora_inicio');

    expect(Cita::count())->toBe(0);
});

it('no registra citas a un médico o un paciente inactivo (RN03, RN04)', function () {
    $this->paciente->update(['activo' => false]);
    $this->actingAs($this->recepcion)->post('/citas', datosCita($this))->assertSessionHasErrors('paciente_id');

    $this->paciente->update(['activo' => true]);
    $this->medico->update(['activo' => false]);
    $this->actingAs($this->recepcion)->post('/citas', datosCita($this))->assertSessionHasErrors('medico_id');

    expect(Cita::count())->toBe(0);
});

it('exige una especialidad que el médico atienda', function () {
    $otra = crearEspecialidad('Pediatría');

    $this->actingAs($this->recepcion)->post('/citas', datosCita($this, ['especialidad_id' => $otra->id]))
        ->assertSessionHasErrors('especialidad_id');

    $this->actingAs($this->recepcion)->post('/citas', datosCita($this, ['especialidad_id' => null]))
        ->assertSessionHasErrors('especialidad_id');
});

it('lleva al formulario solo si el bloque sigue libre', function () {
    $url = '/citas/nueva?medico=' . $this->medico->id . '&fecha=' . proximoLunes() . '&hora=';

    $this->actingAs($this->recepcion)->get($url . '09:00')->assertOk()->assertSee('Registrar cita');

    crearCita($this);
    $this->actingAs($this->recepcion)->get($url . '09:00')->assertRedirect();
});

it('solo la recepcionista registra citas; el paciente consulta disponibilidad pero no registra (RN12)', function () {
    $admin = User::factory()->administrador()->create();
    $medicoUsuario = User::factory()->medico()->create();
    $pacienteUsuario = User::factory()->paciente()->create();

    foreach ([$admin, $medicoUsuario, $pacienteUsuario] as $usuario) {
        $this->actingAs($usuario)->post('/citas', datosCita($this))->assertForbidden();
        $this->actingAs($usuario)->get('/citas/nueva')->assertForbidden();
    }

    $this->actingAs($pacienteUsuario)->get('/citas/disponibilidad')->assertOk();
    $this->actingAs($admin)->get('/citas/disponibilidad')->assertForbidden();
    $this->actingAs($pacienteUsuario)->get('/citas')->assertForbidden();
    expect(Cita::count())->toBe(0);
});

it('redirige a la página pública a quien no inició sesión', function () {
    $this->get('/citas')->assertRedirect('/');
    $this->get('/citas/disponibilidad')->assertRedirect('/');
});

it('la agenda se filtra por médico, fecha y estado; el médico ve solo la suya (CU31)', function () {
    $otro = crearMedico([], [$this->especialidad]);
    crearHorario($otro, 1, '08:00', '12:00');
    crearCita($this, ['paciente_id' => crearPaciente(['apellidos' => 'Zamora'])->id]);
    Cita::create([
        'paciente_id' => crearPaciente(['apellidos' => 'Quiroga'])->id, 'medico_id' => $otro->id, 'especialidad_id' => $this->especialidad->id,
        'fecha' => proximoLunes(), 'hora_inicio' => '10:00', 'hora_fin' => '10:30', 'estado' => 'confirmada',
    ]);

    $admin = User::factory()->administrador()->create();
    $lunes = proximoLunes();

    $this->actingAs($admin)->get("/citas?desde=$lunes&hasta=$lunes")->assertOk()->assertSee('Zamora')->assertSee('Quiroga');
    $this->actingAs($admin)->get("/citas?desde=$lunes&medico={$otro->id}")->assertSee('Quiroga')->assertDontSee('Zamora');
    $this->actingAs($admin)->get("/citas?desde=$lunes&estado=confirmada")->assertSee('Quiroga')->assertDontSee('Zamora');

    $medicoUsuario = User::factory()->medico()->create();
    $this->medico->update(['user_id' => $medicoUsuario->id]);
    $this->actingAs($medicoUsuario->fresh())->get("/citas?desde=$lunes")->assertOk()->assertSee('Zamora')->assertDontSee('Quiroga');
});

it('muestra Citas en el menú de administrador y recepcionista, y Mi agenda al médico', function () {
    $admin = User::factory()->administrador()->create();
    $medicoUsuario = User::factory()->medico()->create();

    $this->actingAs($admin)->get('/dashboard')->assertSee('Citas');
    $this->actingAs($this->recepcion)->get('/dashboard')->assertSee('Citas');
    $this->actingAs($medicoUsuario)->get('/dashboard')->assertSee('Mi agenda');
});
