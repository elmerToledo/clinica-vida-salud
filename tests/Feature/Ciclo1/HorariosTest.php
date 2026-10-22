<?php

use App\Models\HorarioAtencion;
use App\Models\User;

// CU20 a CU23 (RF05, RN04, RN05, RN08).

function datosHorario($medico, array $extra = []): array
{
    return array_merge([
        'medico_id' => $medico->id,
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fin' => '12:00',
        'duracion_cita_min' => 30,
    ], $extra);
}

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->medico = crearMedico(['nombres' => 'Alberto'], [crearEspecialidad()]);
});

it('registra un horario válido y activo (CU20)', function () {
    $this->actingAs($this->admin)->post('/horarios', datosHorario($this->medico))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('horarios_atencion', [
        'medico_id' => $this->medico->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '12:00', 'activo' => true,
    ]);
});

it('rechaza una hora final que no sea posterior a la inicial (RN08)', function (string $fin) {
    $this->actingAs($this->admin)->post('/horarios', datosHorario($this->medico, ['hora_fin' => $fin]))
        ->assertSessionHasErrors('hora_fin');

    expect(HorarioAtencion::count())->toBe(0);
})->with(['07:00', '08:00']);

it('rechaza una duración que no cabe en el horario (RN08)', function () {
    $this->actingAs($this->admin)
        ->post('/horarios', datosHorario($this->medico, ['hora_inicio' => '08:00', 'hora_fin' => '08:20', 'duracion_cita_min' => 30]))
        ->assertSessionHasErrors('duracion_cita_min');
});

it('rechaza duraciones fuera de rango y días inválidos', function () {
    $this->actingAs($this->admin)->post('/horarios', datosHorario($this->medico, ['duracion_cita_min' => 2]))
        ->assertSessionHasErrors('duracion_cita_min');
    $this->actingAs($this->admin)->post('/horarios', datosHorario($this->medico, ['dia_semana' => 9]))
        ->assertSessionHasErrors('dia_semana');
    $this->actingAs($this->admin)->post('/horarios', datosHorario($this->medico, ['hora_inicio' => 'ocho']))
        ->assertSessionHasErrors('hora_inicio');
});

it('rechaza un horario que se traslapa con otro del mismo médico y día (RN05)', function () {
    crearHorario($this->medico, 1, '08:00', '12:00');

    $this->actingAs($this->admin)
        ->post('/horarios', datosHorario($this->medico, ['hora_inicio' => '11:00', 'hora_fin' => '13:00']))
        ->assertSessionHasErrors('hora_inicio');

    expect(HorarioAtencion::count())->toBe(1);
});

it('acepta horarios consecutivos, de otro día o de otro médico', function () {
    crearHorario($this->medico, 1, '08:00', '12:00');

    // Empieza justo cuando termina el anterior: no hay traslape.
    $this->actingAs($this->admin)
        ->post('/horarios', datosHorario($this->medico, ['hora_inicio' => '12:00', 'hora_fin' => '14:00']))
        ->assertSessionHasNoErrors();

    // Mismo horario, otro día.
    $this->actingAs($this->admin)->post('/horarios', datosHorario($this->medico, ['dia_semana' => 2]))
        ->assertSessionHasNoErrors();

    // Mismo horario, otro médico.
    $otro = crearMedico([], [crearEspecialidad('Pediatría')]);
    $this->actingAs($this->admin)->post('/horarios', datosHorario($otro))->assertSessionHasNoErrors();

    expect(HorarioAtencion::count())->toBe(4);
});

it('no registra horarios a un médico inactivo (RN04)', function () {
    $inactivo = crearMedico(['activo' => false]);

    $this->actingAs($this->admin)->post('/horarios', datosHorario($inactivo))
        ->assertSessionHasErrors('medico_id');
});

it('al modificar no se traslapa consigo mismo pero sí con otro horario (CU22)', function () {
    $manana = crearHorario($this->medico, 1, '08:00', '12:00');
    crearHorario($this->medico, 1, '14:00', '18:00');

    $this->actingAs($this->admin)
        ->put(route('horarios.update', $manana), datosHorario($this->medico, ['hora_fin' => '11:00']))
        ->assertSessionHasNoErrors();
    expect(substr($manana->fresh()->hora_fin, 0, 5))->toBe('11:00');

    $this->actingAs($this->admin)
        ->put(route('horarios.update', $manana), datosHorario($this->medico, ['hora_fin' => '15:00']))
        ->assertSessionHasErrors('hora_inicio');
});

it('desactiva sin eliminar y libera el espacio (CU23)', function () {
    $horario = crearHorario($this->medico, 1, '08:00', '12:00');

    $this->actingAs($this->admin)->patch(route('horarios.estado', $horario))->assertSessionHas('exito');
    $this->assertDatabaseHas('horarios_atencion', ['id' => $horario->id, 'activo' => false]);

    // Con el horario desactivado ya se puede registrar otro en la misma franja.
    $this->actingAs($this->admin)->post('/horarios', datosHorario($this->medico))->assertSessionHasNoErrors();
});

it('no reactiva un horario si se traslapa con uno activo', function () {
    crearHorario($this->medico, 1, '08:00', '12:00');
    $inactivo = crearHorario($this->medico, 1, '10:00', '13:00', 30, false);

    $this->actingAs($this->admin)->patch(route('horarios.estado', $inactivo))->assertSessionHas('error');

    expect($inactivo->fresh()->activo)->toBeFalse();
});

it('muestra a la recepcionista los horarios, filtrados por médico (CU21)', function () {
    $otro = crearMedico(['nombres' => 'Beatriz'], [crearEspecialidad('Pediatría')]);
    crearHorario($this->medico, 1, '08:00', '12:00');
    crearHorario($otro, 2, '09:00', '13:00');
    $recepcion = User::factory()->recepcionista()->create();

    $this->actingAs($recepcion)->get('/horarios')->assertOk()->assertSee('Alberto')->assertSee('Beatriz');

    $this->actingAs($recepcion)->get('/horarios?medico=' . $otro->id)
        ->assertOk()->assertSee('Beatriz')->assertDontSee('Alberto');

    $this->actingAs($recepcion)->post('/horarios', datosHorario($this->medico))->assertForbidden();
});

it('muestra al médico solo sus propios horarios (CU21)', function () {
    $cuenta = User::factory()->medico()->create();
    $this->medico->update(['user_id' => $cuenta->id]);
    $otro = crearMedico(['nombres' => 'Beatriz'], [crearEspecialidad('Pediatría')]);
    crearHorario($this->medico, 1, '08:00', '12:00');
    crearHorario($otro, 2, '09:00', '13:00');

    $this->actingAs($cuenta)->get('/horarios')
        ->assertOk()->assertSee('Alberto')->assertDontSee('Beatriz');

    // Aunque pida los de otro médico por la dirección, solo ve los suyos.
    $this->actingAs($cuenta)->get('/horarios?medico=' . $otro->id)
        ->assertOk()->assertSee('Alberto')->assertDontSee('Beatriz');
});

it('un médico sin ficha asociada ve la lista vacía', function () {
    $cuenta = User::factory()->medico()->create();
    crearHorario($this->medico, 1, '08:00', '12:00');

    $this->actingAs($cuenta)->get('/horarios')->assertOk()->assertDontSee('Alberto')
        ->assertSee('Todavía no tiene horarios');
});
