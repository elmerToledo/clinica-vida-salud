<?php

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\User;
use App\Services\CitaService;
use Carbon\CarbonImmutable;

// CU26 a CU30 (RF08, RF09, RF10, RN09, RN10, RN13) y las excepciones de CU22 y CU23 con citas.

function viajarAlLunes(string $hora = '08:10'): string
{
    $lunes = proximoLunes();
    test()->travelTo(CarbonImmutable::createFromFormat('Y-m-d H:i', "$lunes $hora", CitaService::ZONA));

    return $lunes;
}

beforeEach(function () {
    $this->recepcion = User::factory()->recepcionista()->create();
    $this->especialidad = crearEspecialidad('Cardiología');
    $this->medico = crearMedico(['nombres' => 'Alberto', 'puede_marcar_atendida' => true], [$this->especialidad]);
    $this->horario = crearHorario($this->medico, 1, '08:00', '12:00', 30);
    $this->paciente = crearPaciente();
});

// ---- CU26 Reprogramar ----

it('reprograma una cita a otro bloque libre y libera el anterior (CU26)', function () {
    $cita = crearCita($this);

    $this->actingAs($this->recepcion)
        ->patch("/citas/{$cita->id}/reprogramar", ['fecha' => proximoLunes(), 'hora_inicio' => '10:00'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('citas.show', $cita));

    $cita->refresh();
    expect($cita->inicio)->toBe('10:00')->and($cita->fin)->toBe('10:30')->and($cita->estado)->toBe('programada');

    $bloques = app(CitaService::class)->bloques($this->medico, proximoLunes());
    expect($bloques->firstWhere('inicio', '09:00')['estado'])->toBe('libre')
        ->and($bloques->firstWhere('inicio', '10:00')['estado'])->toBe('ocupado');

    expect(Bitacora::where('modulo', 'citas')->where('accion', 'reprogramar')->where('registro_id', $cita->id)->exists())->toBeTrue();
});

it('una cita confirmada vuelve a programada al reprogramarla y puede conservar su propio bloque (CU26)', function () {
    $cita = crearCita($this, ['estado' => 'confirmada']);

    $this->actingAs($this->recepcion)
        ->patch("/citas/{$cita->id}/reprogramar", ['fecha' => proximoLunes(), 'hora_inicio' => '09:00'])
        ->assertSessionHasNoErrors();

    expect($cita->fresh()->estado)->toBe('programada');
});

it('no reprograma a un bloque ocupado, fuera de horario ni del pasado (CU26, RN13)', function () {
    $cita = crearCita($this);
    crearCita($this, ['paciente_id' => crearPaciente()->id, 'hora_inicio' => '10:00', 'hora_fin' => '10:30']);

    foreach (['10:00', '07:00', '08:15'] as $hora) {
        $this->actingAs($this->recepcion)
            ->patch("/citas/{$cita->id}/reprogramar", ['fecha' => proximoLunes(), 'hora_inicio' => $hora])
            ->assertSessionHasErrors('hora_inicio');
    }

    expect($cita->fresh()->inicio)->toBe('09:00');
});

it('solo se reprograman citas programadas o confirmadas (RF10)', function (string $estado) {
    $cita = crearCita($this, ['estado' => $estado]);

    $this->actingAs($this->recepcion)
        ->patch("/citas/{$cita->id}/reprogramar", ['fecha' => proximoLunes(), 'hora_inicio' => '10:00'])
        ->assertSessionHasErrors('estado');

    expect($cita->fresh()->inicio)->toBe('09:00');
})->with(['cancelada', 'atendida', 'inasistencia']);

// ---- CU27 Cancelar ----

it('cancela una cita guardando el motivo y libera el bloque (CU27, RN09)', function () {
    $cita = crearCita($this);

    $this->actingAs($this->recepcion)
        ->patch("/citas/{$cita->id}/cancelar", ['motivo_cancelacion' => 'El paciente avisó que no puede venir'])
        ->assertSessionHasNoErrors();

    $cita->refresh();
    expect($cita->estado)->toBe('cancelada')->and($cita->motivo_cancelacion)->toBe('El paciente avisó que no puede venir');
    expect(app(CitaService::class)->bloques($this->medico, proximoLunes())->firstWhere('inicio', '09:00')['estado'])->toBe('libre');
    expect(Cita::count())->toBe(1);
    expect(Bitacora::where('modulo', 'citas')->where('accion', 'cancelar')->exists())->toBeTrue();
});

it('exige el motivo para cancelar y no cancela dos veces (CU27)', function () {
    $cita = crearCita($this);

    $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/cancelar", ['motivo_cancelacion' => ''])
        ->assertSessionHasErrors('motivo_cancelacion');
    expect($cita->fresh()->estado)->toBe('programada');

    $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/cancelar", ['motivo_cancelacion' => 'Motivo válido']);
    $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/cancelar", ['motivo_cancelacion' => 'Otra vez'])
        ->assertSessionHasErrors('estado');
});

it('el paciente cancela solo sus propias citas (CU27)', function () {
    $usuario = User::factory()->paciente()->create();
    $this->paciente->update(['user_id' => $usuario->id]);

    $propia = crearCita($this);
    $ajena = crearCita($this, ['paciente_id' => crearPaciente()->id, 'hora_inicio' => '10:00', 'hora_fin' => '10:30']);

    $this->actingAs($usuario)->patch("/citas/{$ajena->id}/cancelar", ['motivo_cancelacion' => 'No es mía'])->assertForbidden();
    expect($ajena->fresh()->estado)->toBe('programada');

    $this->actingAs($usuario)->patch("/citas/{$propia->id}/cancelar", ['motivo_cancelacion' => 'Surgió un imprevisto'])
        ->assertSessionHasNoErrors();
    expect($propia->fresh()->estado)->toBe('cancelada');
});

// ---- CU28 Confirmar ----

it('confirma solo una cita programada (CU28, RF10)', function () {
    $cita = crearCita($this);

    $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/confirmar")->assertSessionHasNoErrors();
    expect($cita->fresh()->estado)->toBe('confirmada');

    $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/confirmar")->assertSessionHasErrors('estado');

    $cancelada = crearCita($this, ['estado' => 'cancelada', 'hora_inicio' => '10:00', 'hora_fin' => '10:30']);
    $this->actingAs($this->recepcion)->patch("/citas/{$cancelada->id}/confirmar")->assertSessionHasErrors('estado');
    expect($cancelada->fresh()->estado)->toBe('cancelada');
});

// ---- CU29 Asistencia e inasistencia ----

it('registra la llegada del paciente sin cambiar el estado (CU29)', function () {
    $lunes = viajarAlLunes();
    $cita = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00']);

    $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/asistencia", ['tipo' => 'asistio'])->assertSessionHasNoErrors();

    $cita->refresh();
    expect($cita->llegada_at)->not->toBeNull()->and($cita->estado)->toBe('programada');

    $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/asistencia", ['tipo' => 'asistio'])->assertSessionHasErrors('estado');
    $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/asistencia", ['tipo' => 'inasistencia'])->assertSessionHasErrors('estado');
    expect($cita->fresh()->estado)->toBe('programada');
});

it('registra la inasistencia y conserva la cita (CU29)', function () {
    $lunes = viajarAlLunes();
    $cita = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00', 'estado' => 'confirmada']);

    $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/asistencia", ['tipo' => 'inasistencia'])->assertSessionHasNoErrors();

    expect($cita->fresh()->estado)->toBe('inasistencia')->and(Cita::count())->toBe(1);
    expect(Bitacora::where('modulo', 'citas')->where('accion', 'inasistencia')->exists())->toBeTrue();
});

it('no registra asistencia ni inasistencia de una cita de fecha futura (CU29)', function () {
    $cita = crearCita($this);

    foreach (['asistio', 'inasistencia'] as $tipo) {
        $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/asistencia", ['tipo' => $tipo])->assertSessionHasErrors('estado');
    }

    expect($cita->fresh()->estado)->toBe('programada')->and($cita->fresh()->llegada_at)->toBeNull();
});

// ---- CU30 Marcar como atendida ----

it('el médico con permiso marca su cita como atendida (CU30)', function () {
    $lunes = viajarAlLunes();
    $usuario = User::factory()->medico()->create();
    $this->medico->update(['user_id' => $usuario->id]);
    $cita = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00']);

    $this->actingAs($usuario->fresh())->patch("/citas/{$cita->id}/atender")->assertSessionHasNoErrors();

    expect($cita->fresh()->estado)->toBe('atendida');
    expect(Bitacora::where('modulo', 'citas')->where('accion', 'atender')->exists())->toBeTrue();
});

it('no marca atendida sin permiso, una cita ajena, cancelada o de fecha futura (CU30, RF10)', function () {
    $lunes = viajarAlLunes();
    $usuario = User::factory()->medico()->create();
    $this->medico->update(['user_id' => $usuario->id]);
    $medico = $usuario->fresh();

    $cita = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00']);
    $cancelada = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '09:00', 'hora_fin' => '09:30', 'estado' => 'cancelada']);
    $futura = crearCita($this, ['fecha' => CarbonImmutable::parse($lunes)->addWeek()->toDateString(), 'hora_inicio' => '10:00', 'hora_fin' => '10:30']);

    // Cita cancelada y cita futura: error de estado.
    $this->actingAs($medico)->patch("/citas/{$cancelada->id}/atender")->assertSessionHasErrors('estado');
    $this->actingAs($medico)->patch("/citas/{$futura->id}/atender")->assertSessionHasErrors('estado');

    // Cita de otro médico.
    $otro = crearMedico(['puede_marcar_atendida' => true], [$this->especialidad]);
    $ajena = Cita::create(['paciente_id' => $this->paciente->id, 'medico_id' => $otro->id, 'especialidad_id' => $this->especialidad->id,
        'fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00', 'estado' => 'programada']);
    $this->actingAs($medico)->patch("/citas/{$ajena->id}/atender")->assertForbidden();

    // Sin el permiso asignado.
    $this->medico->update(['puede_marcar_atendida' => false]);
    $this->actingAs($usuario->fresh())->patch("/citas/{$cita->id}/atender")->assertForbidden();

    // Otros roles.
    $this->actingAs($this->recepcion)->patch("/citas/{$cita->id}/atender")->assertForbidden();

    expect($cita->fresh()->estado)->toBe('programada')->and($ajena->fresh()->estado)->toBe('programada');
});

// ---- Detalle de la cita ----

it('cada rol ve en el detalle solo lo que le corresponde', function () {
    $cita = crearCita($this);
    $admin = User::factory()->administrador()->create();

    $this->actingAs($admin)->get("/citas/{$cita->id}")->assertOk()->assertSee($cita->codigo)->assertDontSee('Reprogramar');
    $this->actingAs($this->recepcion)->get("/citas/{$cita->id}")->assertOk()->assertSee('Reprogramar')->assertSee('Confirmar cita');

    $duenio = User::factory()->medico()->create();
    $this->medico->update(['user_id' => $duenio->id]);
    $this->actingAs($duenio->fresh())->get("/citas/{$cita->id}")->assertOk();
    $this->actingAs(User::factory()->medico()->create())->get("/citas/{$cita->id}")->assertForbidden();

    $pacienteUsuario = User::factory()->paciente()->create();
    $this->paciente->update(['user_id' => $pacienteUsuario->id]);
    $this->actingAs($pacienteUsuario)->get("/citas/{$cita->id}")->assertOk()->assertSee('Cancelar cita');
    $this->actingAs(User::factory()->paciente()->create())->get("/citas/{$cita->id}")->assertForbidden();
});

it('una cita cancelada no ofrece acciones de gestión', function () {
    $cita = crearCita($this, ['estado' => 'cancelada', 'motivo_cancelacion' => 'Cambio de planes']);

    $this->actingAs($this->recepcion)->get("/citas/{$cita->id}")
        ->assertOk()
        ->assertSee('Cambio de planes')
        ->assertDontSee('Reprogramar')
        ->assertDontSee('Confirmar cita');
});

// ---- CU22 y CU23: horarios con citas pendientes ----

it('no desactiva un horario con citas futuras pendientes y sí cuando se cancelan (CU23)', function () {
    $admin = User::factory()->administrador()->create();
    $cita = crearCita($this);

    $this->actingAs($admin)->patch("/horarios/{$this->horario->id}/estado")->assertSessionHas('error');
    expect($this->horario->fresh()->activo)->toBeTrue();

    $cita->update(['estado' => 'cancelada', 'motivo_cancelacion' => 'Prueba']);

    $this->actingAs($admin)->patch("/horarios/{$this->horario->id}/estado")->assertSessionHas('exito');
    expect($this->horario->fresh()->activo)->toBeFalse();
});

it('no recorta un horario si deja citas fuera, pero sí lo amplía (CU22)', function () {
    $admin = User::factory()->administrador()->create();
    crearCita($this, ['hora_inicio' => '11:00', 'hora_fin' => '11:30']);

    $datos = ['medico_id' => $this->medico->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00', 'duracion_cita_min' => 30];

    $this->actingAs($admin)->put("/horarios/{$this->horario->id}", $datos)->assertSessionHasErrors('hora_inicio');
    expect(substr($this->horario->fresh()->hora_fin, 0, 5))->toBe('12:00');

    $this->actingAs($admin)->put("/horarios/{$this->horario->id}", array_merge($datos, ['hora_fin' => '13:00']))->assertSessionHasNoErrors();
    expect(substr($this->horario->fresh()->hora_fin, 0, 5))->toBe('13:00');

    // Cambiar el día también deja la cita sin atender.
    $this->actingAs($admin)->put("/horarios/{$this->horario->id}", array_merge($datos, ['hora_fin' => '13:00', 'dia_semana' => 2]))
        ->assertSessionHasErrors('hora_inicio');
});

it('las citas pasadas o canceladas no impiden cambiar un horario', function () {
    $admin = User::factory()->administrador()->create();
    crearCita($this, ['estado' => 'atendida', 'fecha' => CarbonImmutable::now(CitaService::ZONA)->previous(CarbonImmutable::MONDAY)->toDateString()]);
    crearCita($this, ['estado' => 'cancelada', 'hora_inicio' => '11:00', 'hora_fin' => '11:30']);

    $this->actingAs($admin)->patch("/horarios/{$this->horario->id}/estado")->assertSessionHas('exito');
    expect(HorarioAtencion::find($this->horario->id)->activo)->toBeFalse();
});
