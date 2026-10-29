<?php

use App\Models\Cita;
use App\Models\User;

// CU37 a CU42 (RF13): reportes de solo consulta.

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->recepcion = User::factory()->recepcionista()->create();
    $this->especialidad = crearEspecialidad('Cardiología');
    $this->medico = crearMedico(['nombres' => 'Alberto', 'apellidos' => 'Quiroga'], [$this->especialidad]);
    $this->paciente = crearPaciente(['nombres' => 'Lucía', 'apellidos' => 'Gómez']);
});

/** Crea una cita en una fecha fija; cada una usa una hora distinta para no chocar. */
function citaEn($t, string $fecha, string $estado, string $hora = '09:00', array $extra = []): Cita
{
    return crearCita($t, array_merge([
        'fecha' => $fecha,
        'hora_inicio' => $hora,
        'hora_fin' => substr($hora, 0, 2) . ':30',
        'estado' => $estado,
    ], $extra));
}

// ---- Acceso por rol (RN12) ----

it('el administrador entra a los seis reportes', function () {
    foreach (['reportes', 'reportes/citas-por-fecha', 'reportes/citas-por-medico', 'reportes/citas-por-especialidad',
        'reportes/citas-por-estado', 'reportes/pacientes-registrados', 'reportes/atendidas-canceladas'] as $ruta) {
        $this->actingAs($this->admin)->get('/' . $ruta)->assertOk();
    }
});

it('recepción entra a fecha, médico y estado, pero no a los reportes generales', function () {
    foreach (['reportes', 'reportes/citas-por-fecha', 'reportes/citas-por-medico', 'reportes/citas-por-estado'] as $ruta) {
        $this->actingAs($this->recepcion)->get('/' . $ruta)->assertOk();
    }

    foreach (['reportes/citas-por-especialidad', 'reportes/pacientes-registrados', 'reportes/atendidas-canceladas'] as $ruta) {
        $this->actingAs($this->recepcion)->get('/' . $ruta)->assertForbidden();
    }
});

it('el médico y el paciente no ven los reportes', function () {
    foreach ([User::factory()->medico()->create(), User::factory()->paciente()->create()] as $usuario) {
        $this->actingAs($usuario)->get('/reportes')->assertForbidden();
        $this->actingAs($usuario)->get('/reportes/citas-por-fecha')->assertForbidden();
    }
});

it('quien no inició sesión va a la página pública', function () {
    $this->get('/reportes')->assertRedirect('/');
    $this->get('/reportes/citas-por-estado')->assertRedirect('/');
});

it('el menú muestra Reportes solo al administrador y a recepción', function () {
    $this->actingAs($this->admin)->get('/dashboard')->assertSee('Reportes');
    $this->actingAs($this->recepcion)->get('/dashboard')->assertSee('Reportes');
    $this->actingAs(User::factory()->medico()->create())->get('/dashboard')->assertDontSee('Reportes');
    $this->actingAs(User::factory()->paciente()->create())->get('/dashboard')->assertDontSee('Reportes');
});

// ---- CU37 ----

it('lista las citas programadas y confirmadas del rango, sin canceladas ni de otras fechas (CU37)', function () {
    citaEn($this, '2026-11-02', 'programada', '09:00');
    citaEn($this, '2026-11-03', 'confirmada', '10:00');
    $cancelada = citaEn($this, '2026-11-02', 'cancelada', '11:00');
    $atendida = citaEn($this, '2026-11-02', 'atendida', '12:00');
    $fuera = citaEn($this, '2026-11-20', 'programada', '08:00');

    $respuesta = $this->actingAs($this->recepcion)
        ->get('/reportes/citas-por-fecha?desde=2026-11-01&hasta=2026-11-05')
        ->assertOk()
        ->assertSeeText('2 citas')
        ->assertDontSee($cancelada->codigo)
        ->assertDontSee($atendida->codigo)
        ->assertDontSee($fuera->codigo);

    $respuesta->assertSee('Gómez');
});

it('el reporte por fecha avisa cuando no hay citas (CU37)', function () {
    $this->actingAs($this->admin)->get('/reportes/citas-por-fecha?desde=2026-11-01&hasta=2026-11-05')
        ->assertOk()->assertSee('No hay citas programadas ni confirmadas');
});

it('rechaza un rango de fechas inválido (CU37)', function () {
    $this->actingAs($this->admin)->get('/reportes/citas-por-fecha?desde=2026-11-10&hasta=2026-11-01')
        ->assertSessionHasErrors('hasta');

    $this->actingAs($this->admin)->get('/reportes/citas-por-fecha?desde=no-es-fecha')
        ->assertSessionHasErrors('desde');
});

// ---- CU38 y CU39 ----

it('cuenta las citas de cada médico por estado e incluye a quien no tiene citas (CU38)', function () {
    citaEn($this, '2026-11-02', 'atendida', '09:00');
    citaEn($this, '2026-11-02', 'cancelada', '10:00');
    citaEn($this, '2026-11-03', 'programada', '11:00');
    $sinCitas = crearMedico(['nombres' => 'Zoe', 'apellidos' => 'Vacía'], [$this->especialidad]);

    $filas = app(\App\Services\ReporteService::class)->citasPorMedico('2026-11-01', '2026-11-30');

    $fila = $filas->firstWhere('id', $this->medico->id);
    expect((int) $fila->total)->toBe(3)
        ->and((int) $fila->atendida)->toBe(1)
        ->and((int) $fila->cancelada)->toBe(1)
        ->and((int) $fila->programada)->toBe(1)
        ->and((int) $fila->confirmada)->toBe(0)
        ->and((int) $filas->firstWhere('id', $sinCitas->id)->total)->toBe(0)
        ->and($filas->first()->id)->toBe($this->medico->id);

    $this->actingAs($this->recepcion)->get('/reportes/citas-por-medico?desde=2026-11-01&hasta=2026-11-30')
        ->assertOk()->assertSee('Quiroga')->assertSee('Vacía');
});

it('el período del reporte por médico deja fuera las citas de otras fechas (CU38)', function () {
    citaEn($this, '2026-10-05', 'atendida', '09:00');

    $fila = app(\App\Services\ReporteService::class)->citasPorMedico('2026-11-01', '2026-11-30')
        ->firstWhere('id', $this->medico->id);

    expect((int) $fila->total)->toBe(0);
});

it('cuenta las citas por especialidad (CU39)', function () {
    $pediatria = crearEspecialidad('Pediatría');
    $otro = crearMedico(['apellidos' => 'Rojas'], [$pediatria]);
    citaEn($this, '2026-11-02', 'atendida', '09:00');
    citaEn($this, '2026-11-02', 'programada', '10:00');
    citaEn($this, '2026-11-02', 'programada', '14:00', ['medico_id' => $otro->id, 'especialidad_id' => $pediatria->id]);

    $filas = app(\App\Services\ReporteService::class)->citasPorEspecialidad('2026-11-01', '2026-11-30');

    expect((int) $filas->firstWhere('nombre', 'Cardiología')->total)->toBe(2)
        ->and((int) $filas->firstWhere('nombre', 'Pediatría')->total)->toBe(1);

    $this->actingAs($this->admin)->get('/reportes/citas-por-especialidad?desde=2026-11-01&hasta=2026-11-30')
        ->assertOk()->assertSee('Cardiología')->assertSee('Pediatría');
});

// ---- CU40 y CU42 ----

it('cuenta las citas por estado, con los estados sin citas en cero (CU40)', function () {
    citaEn($this, '2026-11-02', 'programada', '09:00');
    citaEn($this, '2026-11-02', 'programada', '10:00');
    citaEn($this, '2026-11-02', 'atendida', '11:00');

    $filas = app(\App\Services\ReporteService::class)->citasPorEstado('2026-11-01', '2026-11-30');

    expect($filas)->toHaveCount(count(Cita::ESTADOS))
        ->and($filas->first())->toMatchArray(['estado' => 'programada', 'total' => 2])
        ->and($filas->firstWhere('estado', 'inasistencia')['total'])->toBe(0);

    $this->actingAs($this->recepcion)->get('/reportes/citas-por-estado?desde=2026-11-01&hasta=2026-11-30')
        ->assertOk()->assertSee('Programada')->assertSee('Ver citas');
});

it('cuenta atendidas, canceladas e inasistencias del período (CU42)', function () {
    citaEn($this, '2026-11-02', 'atendida', '09:00');
    citaEn($this, '2026-11-02', 'atendida', '10:00');
    citaEn($this, '2026-11-02', 'cancelada', '11:00');
    citaEn($this, '2026-11-02', 'inasistencia', '12:00');
    citaEn($this, '2026-11-02', 'programada', '13:00');
    citaEn($this, '2026-12-01', 'atendida', '09:00');

    $datos = app(\App\Services\ReporteService::class)->atendidasCanceladasInasistencias('2026-11-01', '2026-11-30');

    expect($datos)->toBe(['atendidas' => 2, 'canceladas' => 1, 'inasistencias' => 1, 'total' => 4]);

    $this->actingAs($this->admin)->get('/reportes/atendidas-canceladas?desde=2026-11-01&hasta=2026-11-30')
        ->assertOk()->assertSee('Atendidas')->assertSee('50%');
});

// ---- CU41 ----

it('lista los pacientes registrados con filtro de estado y de fecha de alta (CU41)', function () {
    $activo = crearPaciente(['nombres' => 'Activa', 'apellidos' => 'Reciente']);
    $inactivo = crearPaciente(['nombres' => 'Retirado', 'apellidos' => 'Pasado', 'activo' => false]);
    $viejo = crearPaciente(['nombres' => 'Antiguo', 'apellidos' => 'Archivo']);
    $viejo->forceFill(['created_at' => '2020-01-05 10:00:00'])->save();

    // Sin fechas se ven todos: la paciente del beforeEach, la activa, la inactiva y la antigua.
    $this->actingAs($this->admin)->get('/reportes/pacientes-registrados')
        ->assertOk()->assertSee('Reciente')->assertSee('Pasado')->assertSee('Archivo');

    $this->actingAs($this->admin)->get('/reportes/pacientes-registrados?estado=inactivos')
        ->assertOk()->assertSee('Pasado')->assertDontSee('Reciente');

    $this->actingAs($this->admin)->get('/reportes/pacientes-registrados?desde=2021-01-01&hasta=' . now()->addDay()->toDateString())
        ->assertOk()->assertSee('Reciente')->assertDontSee('Archivo');
});
