<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'nombres' => 'Test',
        'apellidos' => 'User',
        'ci' => '9000001',
        'fecha_nacimiento' => '1990-05-20',
        'sexo' => 'M',
        'telefono' => '71234567',
        'email' => 'test@example.com',
        'password' => 'Clave12345',
        'password_confirmation' => 'Clave12345',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
