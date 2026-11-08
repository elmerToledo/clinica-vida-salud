<?php

use App\Models\CorreoEnviado;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use App\Services\CitaService;

// Constancia de correos enviados + manejo amigable de errores.

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->recepcion = User::factory()->recepcionista()->create();
    $this->especialidad = crearEspecialidad('Cardiología');
    $this->medico = crearMedico(['nombres' => 'Alberto'], [$this->especialidad]);
    crearHorario($this->medico, 1, '08:00', '12:00', 30);
    $this->paciente = crearPaciente(['email' => 'paciente@example.com']);
});

function citaDeManana($t)
{
    $manana = CarbonImmutable::createFromFormat('Y-m-d', app(CitaService::class)->hoy())->addDay()->toDateString();

    return crearCita($t, ['fecha' => $manana]);
}

it('registra cada correo enviado en la constancia', function () {
    Mail::raw('Contenido del recordatorio', fn ($m) => $m->to('a@example.com')->subject('Asunto de prueba'));

    $c = CorreoEnviado::firstOrFail();
    expect($c->destinatario)->toBe('a@example.com')
        ->and($c->asunto)->toBe('Asunto de prueba')
        ->and($c->contenido)->toContain('Contenido del recordatorio')
        ->and($c->estado)->toBe('simulado');
});

it('marca como enviado un correo con un mailer real', function () {
    config(['mail.default' => 'smtp']);
    expect(CorreoEnviado::modoSimulado())->toBeFalse();

    config(['mail.default' => 'log']);
    expect(CorreoEnviado::modoSimulado())->toBeTrue();
});

it('solo el administrador ve la constancia y no figura en el menú', function () {
    $this->get('/constancia-correos')->assertRedirect();
    $this->actingAs($this->recepcion)->get('/constancia-correos')->assertForbidden();

    $this->actingAs($this->admin)->get('/dashboard')->assertOk()->assertDontSee('constancia-correos');
});

it('muestra los correos con modo, contenido y filtra por estado', function () {
    CorreoEnviado::create(['destinatario' => 'uno@example.com', 'asunto' => 'Primero', 'contenido' => 'Texto uno', 'estado' => 'simulado']);
    CorreoEnviado::create(['destinatario' => 'dos@example.com', 'asunto' => 'Segundo', 'contenido' => 'Texto dos', 'estado' => 'fallido', 'error' => 'SMTP caído']);

    $this->actingAs($this->admin)->get('/constancia-correos')
        ->assertOk()->assertSee('uno@example.com')->assertSee('Texto dos')->assertSee('SMTP caído')->assertSee('Modo de correo actual');

    $this->actingAs($this->admin)->get('/constancia-correos?estado=fallido')
        ->assertOk()->assertSee('dos@example.com')->assertDontSee('uno@example.com');

    $this->actingAs($this->admin)->get('/constancia-correos?desde=2999-01-01')
        ->assertOk()->assertSee('No hay correos registrados');

    $this->actingAs($this->admin)->get('/constancia-correos?estado=otro')->assertSessionHasErrors('estado');
});

it('si el correo del recordatorio falla no hay error 500: avisa y deja constancia', function () {
    citaDeManana($this);
    Notification::shouldReceive('send')->andThrow(new RuntimeException('SMTP caído'));

    $this->actingAs($this->recepcion)->from('/recordatorios')->post('/recordatorios')
        ->assertRedirect('/recordatorios')
        ->assertSessionHas('error');

    $c = CorreoEnviado::firstOrFail();
    expect($c->estado)->toBe('fallido')->and($c->error)->toContain('SMTP caído')->and($c->destinatario)->toBe('paciente@example.com');
});

it('el comando de recordatorios informa los fallidos', function () {
    citaDeManana($this);
    Notification::shouldReceive('send')->andThrow(new RuntimeException('SMTP caído'));

    $this->artisan('citas:recordatorios')->expectsOutputToContain('fallidos: 1')->assertExitCode(0);
});

it('una falla inesperada al guardar vuelve con un mensaje en vez de una página 500', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->post('/_prueba-falla', fn () => throw new RuntimeException('boom'));

    $this->actingAs($this->admin)->from('/dashboard')->post('/_prueba-falla')
        ->assertRedirect('/dashboard')
        ->assertSessionHas('error');
});

it('muestra pantallas de error en español con botones', function (int $codigo, string $texto) {
    Route::middleware('web')->get("/_error-{$codigo}", fn () => abort($codigo));

    $this->get("/_error-{$codigo}")->assertStatus($codigo)->assertSee($texto)->assertSee('Ir al inicio')->assertSee('Volver atrás');
})->with([
    [403, 'No tiene permiso'],
    [404, 'No encontramos la página'],
    [419, 'Su sesión expiró'],
    [429, 'Demasiados intentos'],
    [500, 'Algo salió mal'],
    [503, 'mantenimiento'],
]);
