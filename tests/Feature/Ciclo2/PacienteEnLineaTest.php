<?php

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\User;
use App\Services\CitaService;
use Carbon\CarbonImmutable;

// CU32 a CU36: cuenta y perfil del paciente, reserva en línea, mis citas e historial.

function datosRegistro(array $extra = []): array
{
    return array_merge([
        'nombres' => 'Lucía',
        'apellidos' => 'Fernández',
        'ci' => '8123456',
        'fecha_nacimiento' => '1995-07-12',
        'sexo' => 'F',
        'telefono' => '71234567',
        'direccion' => 'Calle 1',
        'email' => 'lucia@example.com',
        'password' => 'Clave12345',
        'password_confirmation' => 'Clave12345',
    ], $extra);
}

function pacienteConCuenta($t): array
{
    $usuario = User::factory()->paciente()->create();
    $t->paciente->update(['user_id' => $usuario->id]);

    return [$usuario->fresh(), $t->paciente];
}

beforeEach(function () {
    $this->recepcion = User::factory()->recepcionista()->create();
    $this->especialidad = crearEspecialidad('Cardiología');
    $this->medico = crearMedico(['nombres' => 'Alberto'], [$this->especialidad]);
    crearHorario($this->medico, 1, '08:00', '12:00', 30);
    $this->paciente = crearPaciente();
});

// ---- CU33 Registrar cuenta de paciente ----

it('crea la cuenta con rol paciente y su ficha en una sola operación (CU33)', function () {
    $this->post('/register', datosRegistro(['rol' => 'administrador']))->assertRedirect('/dashboard');

    $usuario = User::where('email', 'lucia@example.com')->firstOrFail();
    $ficha = Paciente::where('ci', '8123456')->firstOrFail();

    expect($usuario->rol)->toBe('paciente')
        ->and($usuario->name)->toBe('Lucía Fernández')
        ->and($ficha->user_id)->toBe($usuario->id)
        ->and($ficha->activo)->toBeTrue()
        ->and($ficha->codigo)->toStartWith('PAC-');
    $this->assertAuthenticatedAs($usuario);
});

it('rechaza datos inválidos y contraseñas débiles al registrarse (CU33)', function () {
    $this->post('/register', datosRegistro(['password' => 'corta', 'password_confirmation' => 'corta']))->assertSessionHasErrors('password');
    $this->post('/register', datosRegistro(['fecha_nacimiento' => '2999-01-01']))->assertSessionHasErrors('fecha_nacimiento');
    $this->post('/register', datosRegistro(['sexo' => 'X']))->assertSessionHasErrors('sexo');
    $this->post('/register', datosRegistro(['ci' => '']))->assertSessionHasErrors('ci');

    expect(User::where('email', 'lucia@example.com')->exists())->toBeFalse()
        ->and(Paciente::where('ci', '8123456')->exists())->toBeFalse();
});

it('rechaza un correo ya usado o una CI que pertenece a otra cuenta (CU33)', function () {
    User::factory()->create(['email' => 'lucia@example.com']);
    $this->post('/register', datosRegistro())->assertSessionHasErrors('email');

    [$usuario, $ficha] = pacienteConCuenta($this);
    $this->post('/register', datosRegistro(['email' => 'otra@example.com', 'ci' => $ficha->ci]))->assertSessionHasErrors('ci');
});

it('vincula la cuenta nueva con la ficha que recepción ya había registrado (CU33)', function () {
    $ficha = crearPaciente(['ci' => '8123456', 'fecha_nacimiento' => '1995-07-12']);
    $cita = crearCita($this, ['paciente_id' => $ficha->id]);

    $this->post('/register', datosRegistro())->assertSessionHasNoErrors();

    $usuario = User::where('email', 'lucia@example.com')->firstOrFail();
    expect(Paciente::where('ci', '8123456')->count())->toBe(1)
        ->and($ficha->fresh()->user_id)->toBe($usuario->id)
        ->and($cita->fresh()->paciente_id)->toBe($ficha->id);
});

it('no vincula una ficha ajena si la fecha de nacimiento no coincide (CU33)', function () {
    crearPaciente(['ci' => '8123456', 'fecha_nacimiento' => '1980-01-01']);

    $this->post('/register', datosRegistro())->assertSessionHasErrors('ci');

    expect(User::where('email', 'lucia@example.com')->exists())->toBeFalse();
});

// ---- CU34 Editar perfil ----

it('el paciente edita solo su propio perfil (CU34)', function () {
    [$usuario, $ficha] = pacienteConCuenta($this);
    $otro = crearPaciente(['telefono' => '70000000']);

    $this->actingAs($usuario)->get('/mi-perfil')->assertOk()->assertSee($ficha->ci);

    $this->actingAs($usuario)->put('/mi-perfil', [
        'ci' => $ficha->ci, 'nombres' => 'Nuevo', 'apellidos' => 'Nombre', 'fecha_nacimiento' => '1990-01-15',
        'sexo' => 'F', 'telefono' => '79998888',
    ])->assertSessionHasNoErrors()->assertRedirect(route('perfil.edit'));

    expect($ficha->fresh()->telefono)->toBe('79998888')->and($ficha->fresh()->nombres)->toBe('Nuevo');
    expect($otro->fresh()->telefono)->toBe('70000000');
});

it('no deja usar la CI de otro paciente al editar el perfil (CU34)', function () {
    [$usuario, $ficha] = pacienteConCuenta($this);
    $otro = crearPaciente();

    $this->actingAs($usuario)->put('/mi-perfil', [
        'ci' => $otro->ci, 'nombres' => 'A', 'apellidos' => 'B', 'fecha_nacimiento' => '1990-01-15', 'sexo' => 'F', 'telefono' => '79998888',
    ])->assertSessionHasErrors('ci');
});

it('una cuenta sin ficha completa su perfil y queda lista para reservar (CU34)', function () {
    $usuario = User::factory()->paciente()->create();

    $this->actingAs($usuario)->get('/mi-perfil')->assertOk()->assertSee('Complete su');
    $this->actingAs($usuario)->put('/mi-perfil', [
        'ci' => '7000111', 'nombres' => 'Sin', 'apellidos' => 'Ficha', 'fecha_nacimiento' => '1988-02-02', 'sexo' => 'M', 'telefono' => '71112222',
    ])->assertSessionHasNoErrors();

    expect($usuario->fresh()->paciente)->not->toBeNull()->and($usuario->fresh()->paciente->ci)->toBe('7000111');
});

it('solo el paciente usa Mis datos y Mis citas', function () {
    foreach ([$this->recepcion, User::factory()->administrador()->create(), User::factory()->medico()->create()] as $usuario) {
        $this->actingAs($usuario)->get('/mi-perfil')->assertForbidden();
        $this->actingAs($usuario)->get('/mis-citas')->assertForbidden();
    }
});

// ---- CU35 Reservar cita en línea ----

it('el paciente reserva una cita a su propio nombre (CU35)', function () {
    [$usuario, $ficha] = pacienteConCuenta($this);
    $ajeno = crearPaciente();

    // Aunque mande el paciente de otra persona, se reserva a nombre del dueño de la sesión.
    $this->actingAs($usuario)->post('/citas', datosCita($this, ['paciente_id' => $ajeno->id]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('mis-citas.index'));

    $cita = Cita::firstOrFail();
    expect($cita->paciente_id)->toBe($ficha->id)->and($cita->creado_por)->toBe($usuario->id);
});

it('el paciente ve la disponibilidad con enlace para reservar y el formulario sin elegir paciente (CU35)', function () {
    [$usuario] = pacienteConCuenta($this);
    $url = '?medico=' . $this->medico->id . '&fecha=' . proximoLunes();

    $this->actingAs($usuario)->get('/citas/disponibilidad' . $url)->assertOk()->assertSee('Reservar');
    $this->actingAs($usuario)->get('/citas/nueva' . $url . '&hora=09:00')->assertOk()->assertSee('Reservar cita')->assertDontSee('name="paciente_id"');
});

it('el paciente sin perfil es llevado a completarlo antes de reservar (CU35)', function () {
    $usuario = User::factory()->paciente()->create();

    $this->actingAs($usuario)->get('/citas/nueva?medico=' . $this->medico->id . '&fecha=' . proximoLunes() . '&hora=09:00')
        ->assertRedirect(route('perfil.edit'));
    $this->actingAs($usuario)->post('/citas', datosCita($this))->assertSessionHasErrors('paciente_id');

    expect(Cita::count())->toBe(0);
});

it('el paciente no puede tener dos citas a la misma hora aunque sean con médicos distintos (RF07)', function () {
    [$usuario, $ficha] = pacienteConCuenta($this);
    $otro = crearMedico([], [$this->especialidad]);
    crearHorario($otro, 1, '08:00', '12:00');

    $this->actingAs($usuario)->post('/citas', datosCita($this))->assertSessionHasNoErrors();
    $this->actingAs($usuario)->post('/citas', datosCita($this, ['medico_id' => $otro->id]))->assertSessionHasErrors('hora_inicio');

    expect(Cita::count())->toBe(1);

    // Una hora distinta sí se permite.
    $this->actingAs($usuario)->post('/citas', datosCita($this, ['medico_id' => $otro->id, 'hora_inicio' => '10:00']))->assertSessionHasNoErrors();
    expect(Cita::count())->toBe(2);
});

// ---- CU36 y CU32 ----

it('Mis citas separa las próximas del historial y muestra solo las propias (CU36)', function () {
    [$usuario, $ficha] = pacienteConCuenta($this);
    $ajeno = crearPaciente(['apellidos' => 'Ajenez']);

    crearCita($this, ['paciente_id' => $ficha->id]);
    crearCita($this, ['paciente_id' => $ficha->id, 'estado' => 'cancelada', 'hora_inicio' => '10:00', 'hora_fin' => '10:30', 'motivo_cancelacion' => 'x']);
    crearCita($this, ['paciente_id' => $ajeno->id, 'hora_inicio' => '11:00', 'hora_fin' => '11:30']);

    $respuesta = $this->actingAs($usuario)->get('/mis-citas')->assertOk();

    $respuesta->assertSee('Próximas citas')->assertSee('Historial')->assertSee('Cancelada')->assertDontSee('Ajenez');
    $respuesta->assertViewHas('proximas', fn ($c) => $c->count() === 1);
    $respuesta->assertViewHas('historial', fn ($c) => $c->count() === 1);
});

it('Mis citas lleva a completar el perfil si la cuenta no tiene ficha (CU36)', function () {
    $this->actingAs(User::factory()->paciente()->create())->get('/mis-citas')->assertRedirect(route('perfil.edit'));
});

it('recepción y administrador consultan el historial de un paciente; los demás no (CU32)', function () {
    $propia = crearCita($this);
    crearCita($this, ['estado' => 'cancelada', 'hora_inicio' => '10:00', 'hora_fin' => '10:30', 'motivo_cancelacion' => 'x']);

    foreach ([$this->recepcion, User::factory()->administrador()->create()] as $usuario) {
        $this->actingAs($usuario)->get("/pacientes/{$this->paciente->id}/historial")
            ->assertOk()->assertSee($propia->codigo)->assertSee('Cancelada');
    }

    $this->actingAs(User::factory()->medico()->create())->get("/pacientes/{$this->paciente->id}/historial")->assertForbidden();
    $this->actingAs(User::factory()->paciente()->create())->get("/pacientes/{$this->paciente->id}/historial")->assertForbidden();
});

it('el menú del paciente incluye reservar, mis citas y mis datos', function () {
    [$usuario] = pacienteConCuenta($this);

    $this->actingAs($usuario)->get('/dashboard')->assertOk()
        ->assertSee('Reservar cita')->assertSee('Mis citas')->assertSee('Mis datos');
});
