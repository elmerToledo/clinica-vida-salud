<?php

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\Receta;
use App\Models\User;
use App\Services\CitaService;
use Carbon\CarbonImmutable;

// Pago del paciente con PayPal (CU49, simulado) y receta médica (el médico escribe, publica e imprime).

beforeEach(function () {
    $this->recepcion = User::factory()->recepcionista()->create();
    $this->especialidad = crearEspecialidad('Cardiología');
    $this->especialidad->update(['precio' => 150]);
    $this->medico = crearMedico(['nombres' => 'Alberto'], [$this->especialidad]);
    $this->horario = crearHorario($this->medico, 1, '08:00', '12:00', 30);
    $this->paciente = crearPaciente();

    $this->cuentaPaciente = User::factory()->paciente()->create();
    $this->paciente->update(['user_id' => $this->cuentaPaciente->id]);
    $this->cuentaMedico = User::factory()->medico()->create();
    $this->medico->update(['user_id' => $this->cuentaMedico->id]);
});

function viajarALaConsulta(): string
{
    $lunes = proximoLunes();
    test()->travelTo(CarbonImmutable::createFromFormat('Y-m-d H:i', "$lunes 08:10", CitaService::ZONA));

    return $lunes;
}

$datosReceta = [
    'diagnostico' => 'Faringitis aguda',
    'medicamentos' => "Paracetamol 500 mg, 1 tableta cada 8 horas por 3 días\nAmoxicilina 500 mg, 1 cápsula cada 8 horas por 7 días",
    'indicaciones' => 'Reposo y abundante líquido.',
];

// ---- Pago del paciente con PayPal ----

it('el paciente paga su cita con PayPal y se registra el pago con su número de operación', function () {
    $cita = crearCita($this);

    $this->actingAs($this->cuentaPaciente)->get("/citas/{$cita->id}/pagar")->assertOk()->assertSee('PayPal')->assertSee('150.00');
    $this->actingAs($this->cuentaPaciente)->post("/citas/{$cita->id}/pagar")->assertSessionHasNoErrors()->assertRedirect("/citas/{$cita->id}");

    $pago = $cita->fresh()->pago;
    expect($pago->metodo)->toBe('paypal')
        ->and($pago->estado)->toBe('pagado')
        ->and((float) $pago->monto)->toBe(150.0)
        ->and($pago->referencia)->toStartWith('PP-')
        ->and($pago->registrado_por)->toBe($this->cuentaPaciente->id);
    expect(Bitacora::where('modulo', 'pagos')->where('accion', 'cobrar')->where('registro_id', $pago->id)->exists())->toBeTrue();

    $this->actingAs($this->cuentaPaciente)->get("/citas/{$cita->id}")->assertSee('Comprobante de pago')->assertSee($pago->referencia);
});

it('el paciente ve el botón de pagar con PayPal y recepción no puede registrar PayPal', function () {
    $cita = crearCita($this);

    $this->actingAs($this->cuentaPaciente)->get("/citas/{$cita->id}")->assertOk()->assertSee('Pagar con PayPal');

    $this->actingAs($this->recepcion)->post("/citas/{$cita->id}/cobro", ['metodo' => 'paypal', 'referencia' => 'PP-1'])->assertSessionHasErrors('metodo');
    expect(Pago::count())->toBe(0);
});

it('no se paga dos veces la cita ni una cita ajena o cancelada con PayPal', function () {
    $cita = crearCita($this);
    $this->actingAs($this->cuentaPaciente)->post("/citas/{$cita->id}/pagar")->assertSessionHasNoErrors();
    $this->actingAs($this->cuentaPaciente)->post("/citas/{$cita->id}/pagar")->assertSessionHasErrors('metodo');
    expect(Pago::count())->toBe(1);

    $otraCuenta = User::factory()->paciente()->create();
    crearPaciente()->update(['user_id' => $otraCuenta->id]);
    $otra = crearCita($this, ['hora_inicio' => '10:00', 'hora_fin' => '10:30']);
    $this->actingAs($otraCuenta->fresh())->post("/citas/{$otra->id}/pagar")->assertForbidden();
    $this->actingAs($otraCuenta->fresh())->get("/citas/{$otra->id}/pagar")->assertForbidden();

    $cancelada = crearCita($this, ['hora_inicio' => '11:00', 'hora_fin' => '11:30', 'estado' => 'cancelada']);
    $this->actingAs($this->cuentaPaciente)->post("/citas/{$cancelada->id}/pagar")->assertSessionHasErrors('metodo');
    expect(Pago::count())->toBe(1);
});

it('solo el paciente puede pagar con PayPal', function (string $rol) {
    $cita = crearCita($this);
    $usuario = User::factory()->{$rol}()->create();

    $this->actingAs($usuario)->post("/citas/{$cita->id}/pagar")->assertForbidden();
    expect(Pago::count())->toBe(0);
})->with(['administrador', 'recepcionista', 'medico']);

// ---- Receta médica ----

it('el médico escribe una receta, la publica y el paciente la ve', function () use (&$datosReceta) {
    $lunes = viajarALaConsulta();
    $cita = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00']);

    $this->actingAs($this->cuentaMedico->fresh())->get("/citas/{$cita->id}/receta")->assertOk()->assertSee('Diagnóstico');
    $this->actingAs($this->cuentaMedico->fresh())->put("/citas/{$cita->id}/receta", $datosReceta)->assertSessionHasNoErrors();

    $receta = $cita->fresh()->receta;
    expect($receta->diagnostico)->toBe('Faringitis aguda')
        ->and($receta->publicada)->toBeFalse()
        ->and($receta->codigo)->toStartWith('REC-')
        ->and($receta->medico_id)->toBe($this->medico->id)
        ->and($receta->paciente_id)->toBe($this->paciente->id);

    // Borrador: el paciente aún no la ve ni la imprime.
    $this->actingAs($this->cuentaPaciente)->get("/citas/{$cita->id}")->assertDontSee('Faringitis aguda');
    $this->actingAs($this->cuentaPaciente)->get("/citas/{$cita->id}/receta/imprimir")->assertForbidden();

    $this->actingAs($this->cuentaMedico->fresh())->patch("/citas/{$cita->id}/receta/publicar")->assertSessionHasNoErrors();
    expect($cita->fresh()->receta->publicada)->toBeTrue()->and($cita->fresh()->receta->publicada_at)->not->toBeNull();

    $this->actingAs($this->cuentaPaciente)->get("/citas/{$cita->id}")->assertOk()->assertSee('Faringitis aguda')->assertSee('Paracetamol 500 mg');
    $this->actingAs($this->cuentaPaciente)->get("/citas/{$cita->id}/receta/imprimir")->assertOk()->assertSee('Receta médica')->assertSee('Amoxicilina 500 mg');

    expect(Bitacora::where('modulo', 'recetas')->whereIn('accion', ['crear', 'publicar'])->count())->toBe(2);
});

it('una receta publicada ya no se puede modificar', function () use (&$datosReceta) {
    $lunes = viajarALaConsulta();
    $cita = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00']);
    $medico = $this->cuentaMedico->fresh();

    $this->actingAs($medico)->put("/citas/{$cita->id}/receta", $datosReceta);
    $this->actingAs($medico)->put("/citas/{$cita->id}/receta", array_merge($datosReceta, ['diagnostico' => 'Amigdalitis']))->assertSessionHasNoErrors();
    expect($cita->fresh()->receta->diagnostico)->toBe('Amigdalitis');

    $this->actingAs($medico)->patch("/citas/{$cita->id}/receta/publicar");
    $this->actingAs($medico)->put("/citas/{$cita->id}/receta", array_merge($datosReceta, ['diagnostico' => 'Otro']))->assertSessionHasErrors('diagnostico');
    expect($cita->fresh()->receta->diagnostico)->toBe('Amigdalitis')->and(Receta::count())->toBe(1);
});

it('la receta exige diagnóstico y medicamentos y no se publica sin guardar', function () {
    $lunes = viajarALaConsulta();
    $cita = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00']);
    $medico = $this->cuentaMedico->fresh();

    $this->actingAs($medico)->put("/citas/{$cita->id}/receta", ['diagnostico' => '', 'medicamentos' => ''])->assertSessionHasErrors(['diagnostico', 'medicamentos']);
    $this->actingAs($medico)->patch("/citas/{$cita->id}/receta/publicar")->assertSessionHasErrors('diagnostico');
    expect(Receta::count())->toBe(0);
});

it('el médico imprime su receta aunque sea borrador', function () use (&$datosReceta) {
    $lunes = viajarALaConsulta();
    $cita = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00']);
    $medico = $this->cuentaMedico->fresh();

    $this->actingAs($medico)->get("/citas/{$cita->id}/receta/imprimir")->assertNotFound();
    $this->actingAs($medico)->put("/citas/{$cita->id}/receta", $datosReceta);
    $this->actingAs($medico)->get("/citas/{$cita->id}/receta/imprimir")->assertOk()->assertSee('Imprimir receta')->assertSee('Borrador');
});

it('no se receta en citas ajenas, futuras, canceladas ni por otros roles', function () use (&$datosReceta) {
    $lunes = viajarALaConsulta();
    $medico = $this->cuentaMedico->fresh();

    $futura = crearCita($this, ['fecha' => CarbonImmutable::parse($lunes)->addWeek()->toDateString(), 'hora_inicio' => '08:30', 'hora_fin' => '09:00']);
    $cancelada = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '09:00', 'hora_fin' => '09:30', 'estado' => 'cancelada']);
    $otro = crearMedico(['nombres' => 'Beatriz'], [$this->especialidad]);
    $ajena = Cita::create(['paciente_id' => $this->paciente->id, 'medico_id' => $otro->id, 'especialidad_id' => $this->especialidad->id,
        'fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00', 'estado' => 'programada']);

    foreach ([$futura, $cancelada, $ajena] as $cita) {
        $this->actingAs($medico)->put("/citas/{$cita->id}/receta", $datosReceta)->assertForbidden();
    }

    $propia = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '10:00', 'hora_fin' => '10:30']);
    foreach (['administrador', 'recepcionista', 'paciente'] as $rol) {
        $usuario = User::factory()->{$rol}()->create();
        $this->actingAs($usuario)->put("/citas/{$propia->id}/receta", $datosReceta)->assertForbidden();
        $this->actingAs($usuario)->patch("/citas/{$propia->id}/receta/publicar")->assertForbidden();
    }
    expect(Receta::count())->toBe(0);
});

it('otro paciente no ve ni imprime la receta de una cita ajena', function () use (&$datosReceta) {
    $lunes = viajarALaConsulta();
    $cita = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00']);
    $this->actingAs($this->cuentaMedico->fresh())->put("/citas/{$cita->id}/receta", $datosReceta);
    $this->actingAs($this->cuentaMedico->fresh())->patch("/citas/{$cita->id}/receta/publicar");

    $intruso = User::factory()->paciente()->create();
    crearPaciente()->update(['user_id' => $intruso->id]);
    $this->actingAs($intruso->fresh())->get("/citas/{$cita->id}/receta/imprimir")->assertForbidden();
    $this->actingAs($intruso->fresh())->get("/citas/{$cita->id}")->assertForbidden();
});

it('la receta publicada aparece en el historial médico del paciente', function () use (&$datosReceta) {
    $lunes = viajarALaConsulta();
    $cita = crearCita($this, ['fecha' => $lunes, 'hora_inicio' => '08:30', 'hora_fin' => '09:00', 'estado' => 'atendida']);
    $medico = $this->cuentaMedico->fresh();
    $this->actingAs($medico)->put("/citas/{$cita->id}/receta", $datosReceta);

    $this->actingAs($this->cuentaPaciente)->get('/historial-medico')->assertOk()->assertDontSee('Ver receta');
    $this->actingAs($this->cuentaMedico->fresh())->get("/mis-pacientes/{$this->paciente->id}")->assertOk()->assertSee('Ver receta');

    $this->actingAs($medico)->patch("/citas/{$cita->id}/receta/publicar");
    $this->actingAs($this->cuentaPaciente)->get('/historial-medico')->assertOk()->assertSee('Ver receta');
});
