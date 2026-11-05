<?php

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\User;
use App\Services\CitaService;
use Carbon\CarbonImmutable;

// CU48 (ficha de atención reutilizable) y CU49 (cobro de la cita).

beforeEach(function () {
    $this->recepcion = User::factory()->recepcionista()->create();
    $this->especialidad = crearEspecialidad('Cardiología');
    $this->especialidad->update(['precio' => 150]);
    $this->medico = crearMedico(['nombres' => 'Alberto'], [$this->especialidad]);
    $this->horario = crearHorario($this->medico, 1, '08:00', '12:00', 30);
    $this->paciente = crearPaciente();
});

function reservarFicha($t, string $hora, ?int $pacienteId = null, ?string $fecha = null): Cita
{
    $t->actingAs($t->recepcion)
        ->post('/citas', datosCita($t, [
            'hora_inicio' => $hora,
            'paciente_id' => $pacienteId ?? crearPaciente()->id,
            'fecha' => $fecha ?? proximoLunes(),
        ]))
        ->assertSessionHasNoErrors();

    return Cita::query()->latest('id')->firstOrFail();
}

// ---- CU48 Ficha ----

it('asigna fichas consecutivas por médico y día al registrar las citas (CU48)', function () {
    $a = reservarFicha($this, '08:00');
    $b = reservarFicha($this, '08:30');
    $c = reservarFicha($this, '09:00');

    expect($a->ficha)->toBe(1)->and($b->ficha)->toBe(2)->and($c->ficha)->toBe(3);
});

it('numera las fichas por separado para cada médico y cada fecha (CU48)', function () {
    $otro = crearMedico(['nombres' => 'Beatriz'], [$this->especialidad]);
    crearHorario($otro, 1, '08:00', '12:00', 30);

    $primera = reservarFicha($this, '08:00');

    $this->actingAs($this->recepcion)
        ->post('/citas', datosCita($this, ['medico_id' => $otro->id, 'paciente_id' => crearPaciente()->id]))
        ->assertSessionHasNoErrors();
    $deOtro = Cita::query()->where('medico_id', $otro->id)->firstOrFail();

    crearHorario($this->medico, 2, '08:00', '12:00', 30);
    $martes = CarbonImmutable::parse(proximoLunes())->addDay()->toDateString();
    $otroDia = reservarFicha($this, '08:00', null, $martes);

    expect($primera->ficha)->toBe(1)->and($deOtro->ficha)->toBe(1)->and($otroDia->ficha)->toBe(1);
});

it('libera la ficha de una cita cancelada y la reutiliza la siguiente cita (CU48, CU27)', function () {
    $a = reservarFicha($this, '08:00');
    $b = reservarFicha($this, '08:30');

    $this->actingAs($this->recepcion)
        ->patch("/citas/{$a->id}/cancelar", ['motivo_cancelacion' => 'El paciente avisó'])
        ->assertSessionHasNoErrors();

    expect($a->fresh()->ficha)->toBeNull()->and($b->fresh()->ficha)->toBe(2);

    $nueva = reservarFicha($this, '09:00');
    expect($nueva->ficha)->toBe(1);
});

it('libera la ficha cuando el paciente no se presenta (CU48, CU29)', function () {
    $lunes = proximoLunes();
    $a = reservarFicha($this, '08:00');
    $b = reservarFicha($this, '08:30');

    $this->travelTo(CarbonImmutable::createFromFormat('Y-m-d H:i', "$lunes 12:30", CitaService::ZONA));

    $this->actingAs($this->recepcion)
        ->patch("/citas/{$a->id}/asistencia", ['tipo' => 'inasistencia'])
        ->assertSessionHasNoErrors();

    $a->refresh();
    expect($a->estado)->toBe('inasistencia')->and($a->ficha)->toBeNull()->and($b->fresh()->ficha)->toBe(2);
    expect(Bitacora::where('modulo', 'citas')->where('accion', 'inasistencia')->where('detalle', 'like', '%Ficha 1 liberada%')->exists())->toBeTrue();
});

it('al reprogramar conserva la ficha el mismo día y toma la siguiente libre si cambia de día (CU48, CU26)', function () {
    crearHorario($this->medico, 2, '08:00', '12:00', 30);
    $martes = CarbonImmutable::parse(proximoLunes())->addDay()->toDateString();

    $a = reservarFicha($this, '08:00');
    $b = reservarFicha($this, '08:30');
    reservarFicha($this, '09:00', null, null);
    $enMartes = reservarFicha($this, '08:00', null, $martes);

    $this->actingAs($this->recepcion)
        ->patch("/citas/{$b->id}/reprogramar", ['fecha' => proximoLunes(), 'hora_inicio' => '10:00'])
        ->assertSessionHasNoErrors();
    expect($b->fresh()->ficha)->toBe(2);

    $this->actingAs($this->recepcion)
        ->patch("/citas/{$a->id}/reprogramar", ['fecha' => $martes, 'hora_inicio' => '09:00'])
        ->assertSessionHasNoErrors();

    expect($a->fresh()->ficha)->toBe(2)->and($enMartes->ficha)->toBe(1);
});

it('muestra la ficha en el detalle y en la agenda (CU48)', function () {
    $cita = reservarFicha($this, '08:00');

    $this->actingAs($this->recepcion)->get("/citas/{$cita->id}")->assertOk()->assertSee('Ficha de atención')->assertSee('N.º 1');
    $this->actingAs($this->recepcion)->get('/citas?desde=' . proximoLunes())->assertOk()->assertSee('Ficha');
});

// ---- CU49 Cobro ----

it('la recepcionista registra el cobro en efectivo con el precio de la especialidad (CU49)', function () {
    $cita = crearCita($this);

    $this->actingAs($this->recepcion)
        ->post("/citas/{$cita->id}/cobro", ['metodo' => 'efectivo'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('citas.show', $cita));

    $pago = $cita->fresh()->pago;
    expect($pago->estado)->toBe('pagado')
        ->and((float) $pago->monto)->toBe(150.0)
        ->and($pago->metodo)->toBe('efectivo')
        ->and($pago->codigo)->toBe('PAG-' . str_pad((string) $pago->id, 5, '0', STR_PAD_LEFT))
        ->and($pago->registrado_por)->toBe($this->recepcion->id);

    expect(Bitacora::where('modulo', 'pagos')->where('accion', 'cobrar')->where('registro_id', $pago->id)->exists())->toBeTrue();
});

it('los pagos con tarjeta o PayPal exigen el número de operación (CU49)', function (string $metodo) {
    $cita = crearCita($this);

    $this->actingAs($this->recepcion)
        ->post("/citas/{$cita->id}/cobro", ['metodo' => $metodo])
        ->assertSessionHasErrors('referencia');
    expect($cita->fresh()->pago)->toBeNull();

    $this->actingAs($this->recepcion)
        ->post("/citas/{$cita->id}/cobro", ['metodo' => $metodo, 'referencia' => 'OP-2026-0001'])
        ->assertSessionHasNoErrors();
    expect($cita->fresh()->pago->referencia)->toBe('OP-2026-0001');
})->with(['tarjeta_credito', 'tarjeta_debito', 'paypal']);

it('rechaza un método de pago inválido o una referencia con símbolos (CU49)', function () {
    $cita = crearCita($this);

    $this->actingAs($this->recepcion)->post("/citas/{$cita->id}/cobro", ['metodo' => 'yape'])->assertSessionHasErrors('metodo');
    $this->actingAs($this->recepcion)->post("/citas/{$cita->id}/cobro", [])->assertSessionHasErrors('metodo');
    $this->actingAs($this->recepcion)->post("/citas/{$cita->id}/cobro", ['metodo' => 'paypal', 'referencia' => 'AB<script>'])->assertSessionHasErrors('referencia');
    expect(Pago::count())->toBe(0);
});

it('no cobra dos veces la misma cita (CU49)', function () {
    $cita = crearCita($this);

    $this->actingAs($this->recepcion)->post("/citas/{$cita->id}/cobro", ['metodo' => 'efectivo'])->assertSessionHasNoErrors();
    $this->actingAs($this->recepcion)->post("/citas/{$cita->id}/cobro", ['metodo' => 'efectivo'])->assertSessionHasErrors('metodo');

    expect(Pago::count())->toBe(1);
});

it('no cobra citas canceladas ni con inasistencia (CU49)', function (string $estado) {
    $cita = crearCita($this, ['estado' => $estado]);

    $this->actingAs($this->recepcion)->post("/citas/{$cita->id}/cobro", ['metodo' => 'efectivo'])->assertSessionHasErrors('metodo');

    expect(Pago::count())->toBe(0);
})->with(['cancelada', 'inasistencia']);

it('no cobra si la especialidad no tiene precio (CU49)', function () {
    $this->especialidad->update(['precio' => 0]);
    $cita = crearCita($this);

    $this->actingAs($this->recepcion)->post("/citas/{$cita->id}/cobro", ['metodo' => 'efectivo'])->assertSessionHasErrors('metodo');

    expect(Pago::count())->toBe(0);
});

it('al cancelar una cita pagada el pago queda anulado (CU49, CU27)', function () {
    $cita = crearCita($this);
    $this->actingAs($this->recepcion)->post("/citas/{$cita->id}/cobro", ['metodo' => 'efectivo']);

    $this->actingAs($this->recepcion)
        ->patch("/citas/{$cita->id}/cancelar", ['motivo_cancelacion' => 'Cambio de planes'])
        ->assertSessionHasNoErrors();

    $pago = $cita->fresh()->pago;
    expect($pago->estado)->toBe('anulado');
    expect(Bitacora::where('modulo', 'pagos')->where('accion', 'anular')->where('registro_id', $pago->id)->exists())->toBeTrue();
});

it('solo la recepcionista registra cobros (CU49, RN12)', function (string $rol) {
    $cita = crearCita($this);
    $usuario = User::factory()->{$rol}()->create();

    $this->actingAs($usuario)->post("/citas/{$cita->id}/cobro", ['metodo' => 'efectivo'])->assertForbidden();

    expect(Pago::count())->toBe(0);
})->with(['administrador', 'medico', 'paciente']);

it('el comprobante lo ven recepción y el paciente dueño, no el médico (CU49)', function () {
    $cita = crearCita($this);
    $this->actingAs($this->recepcion)->post("/citas/{$cita->id}/cobro", ['metodo' => 'paypal', 'referencia' => 'PP-77']);

    $this->actingAs($this->recepcion)->get("/citas/{$cita->id}")->assertOk()->assertSee('Comprobante de pago')->assertSee('PP-77');

    $cuenta = User::factory()->paciente()->create();
    $this->paciente->update(['user_id' => $cuenta->id]);
    $this->actingAs($cuenta)->get("/citas/{$cita->id}")->assertOk()->assertSee('Comprobante de pago');

    $medico = User::factory()->medico()->create();
    $this->medico->update(['user_id' => $medico->id]);
    $this->actingAs($medico)->get("/citas/{$cita->id}")->assertOk()->assertDontSee('Comprobante de pago');
});

it('el administrador define el precio de la especialidad (CU49)', function () {
    $admin = User::factory()->administrador()->create();

    $this->actingAs($admin)
        ->put("/especialidades/{$this->especialidad->id}", ['nombre' => 'Cardiología', 'precio' => '180.50'])
        ->assertSessionHasNoErrors();
    expect((float) $this->especialidad->fresh()->precio)->toBe(180.5);

    $this->actingAs($admin)
        ->put("/especialidades/{$this->especialidad->id}", ['nombre' => 'Cardiología', 'precio' => '-5'])
        ->assertSessionHasErrors('precio');
});
