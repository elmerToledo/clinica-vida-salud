<?php

use App\Models\User;

// CU03 a CU06 (RF01, RN12).

function datosUsuario(array $extra = []): array
{
    return array_merge([
        'name' => 'Rosa Recepción',
        'usuario' => 'rosa',
        'email' => 'rosa@vidasalud.com',
        'rol' => 'recepcionista',
        'password' => 'Clave12345',
        'password_confirmation' => 'Clave12345',
        'activo' => '1',
    ], $extra);
}

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

it('registra un usuario con rol y contraseña cifrada (CU03)', function () {
    $this->actingAs($this->admin)->post('/usuarios', datosUsuario())
        ->assertRedirect(route('usuarios.index'));

    $usuario = User::where('usuario', 'rosa')->firstOrFail();

    expect($usuario->rol)->toBe('recepcionista')
        ->and($usuario->activo)->toBeTrue()
        ->and($usuario->password)->not->toBe('Clave12345')
        ->and(Hash::check('Clave12345', $usuario->password))->toBeTrue();
});

it('rechaza usuario o correo repetidos', function () {
    User::factory()->create(['usuario' => 'rosa', 'email' => 'rosa@vidasalud.com']);

    $this->actingAs($this->admin)->post('/usuarios', datosUsuario())
        ->assertSessionHasErrors(['usuario', 'email']);
});

it('rechaza contraseñas débiles o sin confirmar', function () {
    $this->actingAs($this->admin)->post('/usuarios', datosUsuario(['password' => 'abc', 'password_confirmation' => 'abc']))
        ->assertSessionHasErrors('password');

    $this->actingAs($this->admin)->post('/usuarios', datosUsuario(['password' => 'soloLetras', 'password_confirmation' => 'soloLetras']))
        ->assertSessionHasErrors('password');

    $this->actingAs($this->admin)->post('/usuarios', datosUsuario(['password_confirmation' => 'Distinta12345']))
        ->assertSessionHasErrors('password');
});

it('rechaza roles inexistentes y usuarios con formato inválido', function () {
    $this->actingAs($this->admin)->post('/usuarios', datosUsuario(['rol' => 'superadmin']))
        ->assertSessionHasErrors('rol');

    $this->actingAs($this->admin)->post('/usuarios', datosUsuario(['usuario' => 'Rosa Perez!']))
        ->assertSessionHasErrors('usuario');
});

it('lista y busca usuarios sin mostrar contraseñas (CU04)', function () {
    User::factory()->recepcionista()->create(['name' => 'Rosa Quispe', 'usuario' => 'rquispe']);
    User::factory()->medico()->create(['name' => 'Juan Salinas', 'usuario' => 'jsalinas']);

    $this->actingAs($this->admin)->get('/usuarios?q=quispe')
        ->assertOk()->assertSee('Rosa Quispe')->assertDontSee('Juan Salinas');

    $this->actingAs($this->admin)->get('/usuarios?rol=medico')
        ->assertOk()->assertSee('Juan Salinas')->assertDontSee('Rosa Quispe');
});

it('modifica datos y rol sin cambiar la contraseña si se deja vacía (CU05)', function () {
    $usuario = User::factory()->recepcionista()->create(['usuario' => 'rosa', 'password' => 'Clave12345']);
    $hashAntes = $usuario->password;

    $this->actingAs($this->admin)->put(route('usuarios.update', $usuario), datosUsuario([
        'email' => $usuario->email, 'rol' => 'medico', 'password' => '', 'password_confirmation' => '',
    ]))->assertSessionHasNoErrors();

    $usuario->refresh();
    expect($usuario->rol)->toBe('medico')->and($usuario->password)->toBe($hashAntes);
});

it('cambia la contraseña cuando se escribe una nueva', function () {
    $usuario = User::factory()->create(['usuario' => 'rosa', 'password' => 'Clave12345']);

    $this->actingAs($this->admin)->put(route('usuarios.update', $usuario), datosUsuario([
        'email' => $usuario->email, 'password' => 'Nueva54321', 'password_confirmation' => 'Nueva54321',
    ]))->assertSessionHasNoErrors();

    expect(Hash::check('Nueva54321', $usuario->fresh()->password))->toBeTrue();
});

it('no deja al administrador quitarse su rol ni desactivarse', function () {
    $this->actingAs($this->admin)->put(route('usuarios.update', $this->admin), datosUsuario([
        'name' => $this->admin->name, 'usuario' => $this->admin->usuario, 'email' => $this->admin->email,
        'rol' => 'recepcionista', 'password' => '', 'password_confirmation' => '',
    ]))->assertSessionHasErrors('rol');

    expect($this->admin->fresh()->rol)->toBe('administrador');

    $this->actingAs($this->admin)->patch(route('usuarios.estado', $this->admin))->assertSessionHas('error');
    expect($this->admin->fresh()->activo)->toBeTrue();
});

it('desactiva y reactiva a otro usuario sin tocar su rol ni su contraseña (CU06)', function () {
    $usuario = User::factory()->recepcionista()->create(['usuario' => 'rosa', 'password' => 'Clave12345']);
    $hash = $usuario->password;

    $this->actingAs($this->admin)->patch(route('usuarios.estado', $usuario))->assertSessionHas('exito');
    expect($usuario->fresh()->activo)->toBeFalse();

    $this->actingAs($this->admin)->patch(route('usuarios.estado', $usuario));
    $usuario->refresh();

    expect($usuario->activo)->toBeTrue()
        ->and($usuario->rol)->toBe('recepcionista')
        ->and($usuario->password)->toBe($hash);
});

it('solo el administrador gestiona usuarios', function () {
    foreach (['recepcionista', 'medico', 'paciente'] as $rol) {
        $this->actingAs(User::factory()->{$rol}()->create())->post('/usuarios', datosUsuario())->assertForbidden();
    }
});
