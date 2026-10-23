<?php

use App\Models\User;

// CU01: iniciar sesión con usuario o correo. CU33 (parcial): el registro público crea pacientes.

it('permite ingresar con el nombre de usuario', function () {
    $usuario = User::factory()->recepcionista()->create(['usuario' => 'rosa', 'password' => 'Clave12345']);

    $this->post('/login', ['email' => 'rosa', 'password' => 'Clave12345'])
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($usuario);
});

it('permite ingresar con el correo sin importar mayúsculas', function () {
    $usuario = User::factory()->create(['email' => 'rosa@vidasalud.com', 'password' => 'Clave12345']);

    $this->post('/login', ['email' => 'ROSA@vidasalud.com', 'password' => 'Clave12345'])
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($usuario);
});

it('rechaza una contraseña incorrecta con un mensaje genérico', function () {
    User::factory()->create(['usuario' => 'rosa', 'password' => 'Clave12345']);

    $this->from('/login')->post('/login', ['email' => 'rosa', 'password' => 'otra-clave'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'Usuario o contraseña incorrectos.']);

    $this->assertGuest();
});

it('rechaza un usuario que no existe con el mismo mensaje', function () {
    $this->from('/login')->post('/login', ['email' => 'nadie', 'password' => 'Clave12345'])
        ->assertSessionHasErrors(['email' => 'Usuario o contraseña incorrectos.']);

    $this->assertGuest();
});

it('no deja entrar a una cuenta desactivada aunque la contraseña sea correcta', function () {
    User::factory()->inactivo()->create(['usuario' => 'baja', 'password' => 'Clave12345']);

    $this->post('/login', ['email' => 'baja', 'password' => 'Clave12345'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('exige usuario y contraseña', function () {
    $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
});

it('crea con rol paciente a quien se registra desde la pantalla pública', function () {
    $this->post('/register', [
        'nombres' => 'Nuevo',
        'apellidos' => 'Paciente',
        'ci' => '9000002',
        'fecha_nacimiento' => '1992-03-10',
        'sexo' => 'F',
        'telefono' => '71234567',
        'email' => 'nuevo@example.com',
        'password' => 'Clave12345',
        'password_confirmation' => 'Clave12345',
        'rol' => 'administrador', // un intento de elegirse otro rol debe ignorarse
    ])->assertRedirect('/dashboard');

    $usuario = User::where('email', 'nuevo@example.com')->firstOrFail();

    expect($usuario->rol)->toBe('paciente')
        ->and($usuario->activo)->toBeTrue()
        ->and($usuario->usuario)->toBe('nuevo');
});

it('puede cerrar sesión', function () {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});
