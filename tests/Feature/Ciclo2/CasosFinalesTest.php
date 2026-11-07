<?php

use App\Models\Bitacora;
use App\Models\Bloqueo;
use App\Models\Cita;
use App\Models\User;
use App\Notifications\RecordatorioCita;
use App\Services\CitaService;
use App\Services\RecordatorioService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

// CU43 recordatorios, CU44 historial médico, CU45 exportar reportes, CU46 feriados y bloqueos.

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->recepcion = User::factory()->recepcionista()->create();
    $this->especialidad = crearEspecialidad('Cardiología');
    $this->especialidad->update(['precio' => 150]);
    $this->medico = crearMedico(['nombres' => 'Alberto'], [$this->especialidad]);
    crearHorario($this->medico, 1, '08:00', '12:00', 30);
    $this->paciente = crearPaciente(['email' => 'paciente@example.com']);
});

function manana(): string
{
    return CarbonImmutable::createFromFormat('Y-m-d', app(CitaService::class)->hoy())->addDay()->toDateString();
}

function bloqueoDelLunes($t, array $extra = []): Bloqueo
{
    return Bloqueo::create(array_merge([
        'medico_id' => null,
        'fecha_inicio' => proximoLunes(),
        'fecha_fin' => proximoLunes(),
        'motivo' => 'Feriado nacional',
        'activo' => true,
    ], $extra));
}

// ---- CU46 Feriados y bloqueos ----

it('el administrador registra un feriado y queda en la bitácora (CU46)', function () {
    $this->actingAs($this->admin)->post('/bloqueos', [
        'medico_id' => '',
        'fecha_inicio' => proximoLunes(),
        'fecha_fin' => proximoLunes(),
        'motivo' => 'Feriado nacional',
    ])->assertRedirect('/bloqueos')->assertSessionHasNoErrors();

    $b = Bloqueo::firstOrFail();
    expect($b->medico_id)->toBeNull()->and($b->activo)->toBeTrue();
    expect(Bitacora::where('modulo', 'bloqueos')->where('accion', 'crear')->exists())->toBeTrue();
});

it('no permite reservar una cita en un día bloqueado y explica el motivo (CU46)', function () {
    bloqueoDelLunes($this);

    $this->actingAs($this->recepcion)
        ->post('/citas', datosCita($this))
        ->assertSessionHasErrors('hora_inicio');

    expect(Cita::count())->toBe(0);
    expect(app(CitaService::class)->bloques($this->medico, proximoLunes()))->toBeEmpty();
});

it('un bloqueo de un médico no afecta a los demás (CU46)', function () {
    $otro = crearMedico(['nombres' => 'Beatriz'], [$this->especialidad]);
    crearHorario($otro, 1, '08:00', '12:00', 30);
    bloqueoDelLunes($this, ['medico_id' => $this->medico->id, 'motivo' => 'Vacaciones']);

    expect(app(CitaService::class)->bloques($this->medico, proximoLunes()))->toBeEmpty();
    expect(app(CitaService::class)->bloques($otro, proximoLunes()))->not->toBeEmpty();
});

it('al desactivar el bloqueo la agenda se reabre (CU46)', function () {
    $b = bloqueoDelLunes($this);

    $this->actingAs($this->admin)->patch("/bloqueos/{$b->id}/estado")->assertSessionHas('exito');
    expect($b->fresh()->activo)->toBeFalse();
    expect(app(CitaService::class)->bloques($this->medico, proximoLunes()))->not->toBeEmpty();
});

it('avisa si ya hay citas agendadas en el período bloqueado (CU46)', function () {
    crearCita($this);

    $this->actingAs($this->admin)->post('/bloqueos', [
        'fecha_inicio' => proximoLunes(),
        'fecha_fin' => proximoLunes(),
        'motivo' => 'Feriado',
    ])->assertSessionHas('error');

    // La cita ya agendada no se cancela sola.
    expect(Cita::first()->estado)->toBe('programada');
});

it('valida el rango de fechas y solo el administrador gestiona bloqueos (CU46)', function () {
    $this->actingAs($this->admin)->post('/bloqueos', [
        'fecha_inicio' => '2026-12-10',
        'fecha_fin' => '2026-12-01',
        'motivo' => 'x',
    ])->assertSessionHasErrors('fecha_fin');

    $this->actingAs($this->recepcion)->get('/bloqueos')->assertForbidden();
});

// ---- CU43 Recordatorios ----

it('envía el recordatorio por la campanita y el correo si el paciente tiene cuenta (CU43)', function () {
    Notification::fake();
    $usuario = User::factory()->paciente()->create();
    $this->paciente->update(['user_id' => $usuario->id]);
    $cita = crearCita($this, ['fecha' => manana()]);

    $r = app(RecordatorioService::class)->enviarPendientes();

    expect($r['enviados'])->toBe(1);
    Notification::assertSentTo($usuario, RecordatorioCita::class);
    expect($cita->fresh()->recordatorio_enviado_at)->not->toBeNull();
});

it('no envía dos veces el recordatorio de la misma cita (CU43)', function () {
    Notification::fake();
    $this->paciente->update(['user_id' => User::factory()->paciente()->create()->id]);
    crearCita($this, ['fecha' => manana()]);

    app(RecordatorioService::class)->enviarPendientes();
    $r = app(RecordatorioService::class)->enviarPendientes();

    expect($r['enviados'])->toBe(0)->and($r['ya_enviados'])->toBe(1);
    Notification::assertCount(1);
});

it('un paciente sin cuenta recibe solo el correo y sin medio se cuenta aparte (CU43)', function () {
    Notification::fake();
    crearCita($this, ['fecha' => manana()]);
    $sinNada = crearPaciente(['email' => null]);
    Cita::create(['paciente_id' => $sinNada->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->especialidad->id,
        'fecha' => manana(), 'hora_inicio' => '10:00', 'hora_fin' => '10:30', 'estado' => 'programada']);

    $r = app(RecordatorioService::class)->enviarPendientes();

    expect($r['enviados'])->toBe(1)->and($r['sin_medio'])->toBe(1);
    Notification::assertSentOnDemand(RecordatorioCita::class);
});

it('solo recuerda citas vigentes de mañana (CU43)', function () {
    Notification::fake();
    $this->paciente->update(['user_id' => User::factory()->paciente()->create()->id]);
    crearCita($this, ['fecha' => manana(), 'estado' => 'cancelada']);
    crearCita($this, ['fecha' => proximoLunes(), 'hora_inicio' => '10:00', 'hora_fin' => '10:30']);

    expect(app(RecordatorioService::class)->enviarPendientes()['enviados'])->toBe(0);
    Notification::assertNothingSent();
});

it('recepción envía los recordatorios desde la pantalla y el comando funciona (CU43)', function () {
    Notification::fake();
    $this->paciente->update(['user_id' => User::factory()->paciente()->create()->id]);
    crearCita($this, ['fecha' => manana()]);

    $this->actingAs($this->recepcion)->get('/recordatorios')->assertOk();
    $this->actingAs($this->recepcion)->post('/recordatorios')->assertSessionHas('exito');
    Notification::assertCount(1);

    $this->artisan('citas:recordatorios')->assertSuccessful();
});

it('el paciente ve solo sus avisos y los marca como leídos (CU43)', function () {
    $usuario = User::factory()->paciente()->create();
    $this->paciente->update(['user_id' => $usuario->id]);
    crearCita($this, ['fecha' => manana()]);
    app(RecordatorioService::class)->enviarPendientes();

    $otro = User::factory()->paciente()->create();

    $this->actingAs($usuario)->get('/notificaciones')->assertOk()->assertSee('Recordatorio de cita');
    $this->actingAs($otro)->get('/notificaciones')->assertOk()->assertDontSee('Recordatorio de cita');

    $this->actingAs($usuario)->post('/notificaciones/leer-todas');
    expect($usuario->unreadNotifications()->count())->toBe(0);
    $this->actingAs($this->recepcion)->get('/recordatorios')->assertOk();
});

// ---- CU44 Historial médico ----

it('el paciente ve en su historial solo las atenciones realizadas propias (CU44)', function () {
    $usuario = User::factory()->paciente()->create();
    $this->paciente->update(['user_id' => $usuario->id]);
    crearCita($this, ['estado' => 'atendida', 'motivo_consulta' => 'Dolor de pecho']);
    crearCita($this, ['hora_inicio' => '10:00', 'hora_fin' => '10:30', 'motivo_consulta' => 'Control futuro']);
    $ajena = crearPaciente();
    Cita::create(['paciente_id' => $ajena->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->especialidad->id,
        'fecha' => proximoLunes(), 'hora_inicio' => '11:00', 'hora_fin' => '11:30', 'estado' => 'atendida', 'motivo_consulta' => 'Ajena']);

    $this->actingAs($usuario)->get('/historial-medico')->assertOk()
        ->assertSee('Dolor de pecho')->assertDontSee('Control futuro')->assertDontSee('Ajena');
});

it('el médico ve solo los pacientes que atendió y su historial (CU44)', function () {
    $usuarioMedico = User::factory()->medico()->create();
    $this->medico->update(['user_id' => $usuarioMedico->id]);
    crearCita($this, ['estado' => 'atendida']);
    $noAtendido = crearPaciente();

    $this->actingAs($usuarioMedico)->get('/mis-pacientes')->assertOk()->assertSee($this->paciente->apellidos)->assertDontSee($noAtendido->apellidos);
    $this->actingAs($usuarioMedico)->get("/mis-pacientes/{$this->paciente->id}")->assertOk();
    $this->actingAs($usuarioMedico)->get("/mis-pacientes/{$noAtendido->id}")->assertForbidden();
    $this->actingAs(User::factory()->paciente()->create())->get('/mis-pacientes')->assertForbidden();
});

// ---- CU45 Exportar reportes ----

it('exporta un reporte a CSV con los mismos filtros y lo anota en la bitácora (CU45)', function () {
    crearCita($this);

    $r = $this->actingAs($this->admin)->get('/reportes/fecha/exportar?desde=' . proximoLunes() . '&hasta=' . proximoLunes());

    $r->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $csv = $r->streamedContent();
    expect($csv)->toContain('Fecha;Hora;Ficha;Paciente')->and($csv)->toContain($this->paciente->apellidos);
    expect(Bitacora::where('accion', 'exportar')->exists())->toBeTrue();
});

it('cada rol exporta solo los reportes que puede ver (CU45)', function () {
    $this->actingAs($this->recepcion)->get('/reportes/estado/exportar')->assertOk();
    $this->actingAs($this->recepcion)->get('/reportes/especialidad/exportar')->assertNotFound();
    $this->actingAs($this->admin)->get('/reportes/especialidad/exportar')->assertOk();
    $this->actingAs(User::factory()->paciente()->create())->get('/reportes/estado/exportar')->assertForbidden();
});
