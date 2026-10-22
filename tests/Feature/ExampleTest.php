<?php

use App\Models\User;

test('la raíz muestra la página pública de la clínica', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Clínica Santa Aurora')
        ->assertSee('Iniciar sesión')
        ->assertSee('Reservar cita');
});

test('quien ya inició sesión pasa de la raíz al inicio del sistema', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/dashboard');
});
