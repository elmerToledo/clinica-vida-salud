<?php

use App\Models\User;

test('la raíz muestra la página pública de la clínica', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Clínica Santa Aurora')
        ->assertSee('Iniciar sesión')
        ->assertSee('Reservar cita');
});

test('quien ya inició sesión ve la página pública con la opción de continuar o cerrar sesión', function () {
    $this->actingAs(User::factory()->create())->get('/')
        ->assertOk()
        ->assertSee('Tiene una sesión abierta')
        ->assertSee('Continuar al panel');
});
