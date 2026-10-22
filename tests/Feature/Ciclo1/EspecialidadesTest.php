<?php

use App\Models\Especialidad;
use App\Models\User;

// CU16 a CU19 (RF04).

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->recepcion = User::factory()->recepcionista()->create();
});

it('normaliza el nombre sin tildes, mayúsculas ni espacios de más', function () {
    expect(Especialidad::normalizar('  PEDIATRÍA  '))->toBe('pediatria')
        ->and(Especialidad::normalizar('Medicina   General'))->toBe('medicina general');
});

it('registra una especialidad (CU16)', function () {
    $this->actingAs($this->admin)->post('/especialidades', ['nombre' => 'Cardiología', 'descripcion' => 'Corazón'])
        ->assertRedirect(route('especialidades.index'));

    $this->assertDatabaseHas('especialidades', ['nombre' => 'Cardiología', 'nombre_normalizado' => 'cardiologia', 'activo' => true]);
});

it('rechaza un nombre repetido aunque cambien tildes, mayúsculas o espacios (RF04)', function (string $intento) {
    crearEspecialidad('Pediatría');

    $this->actingAs($this->admin)->post('/especialidades', ['nombre' => $intento])
        ->assertSessionHasErrors('nombre');

    expect(Especialidad::count())->toBe(1);
})->with(['pediatria', ' PEDIATRÍA ', 'Pediatría']);

it('exige el nombre', function () {
    $this->actingAs($this->admin)->post('/especialidades', ['nombre' => ''])->assertSessionHasErrors('nombre');
});

it('permite modificar sin cambiar el nombre y rechaza el de otra especialidad (CU18)', function () {
    $pediatria = crearEspecialidad('Pediatría');
    crearEspecialidad('Ginecología');

    $this->actingAs($this->admin)
        ->put(route('especialidades.update', $pediatria), ['nombre' => 'Pediatría', 'descripcion' => 'Niños'])
        ->assertSessionHasNoErrors();
    expect($pediatria->fresh()->descripcion)->toBe('Niños');

    $this->actingAs($this->admin)
        ->put(route('especialidades.update', $pediatria), ['nombre' => 'ginecologia'])
        ->assertSessionHasErrors('nombre');
});

it('desactiva sin eliminar y avisa cuántos médicos activos la atienden (CU19)', function () {
    $especialidad = crearEspecialidad('Odontología');
    crearMedico([], [$especialidad]);

    $this->actingAs($this->admin)->patch(route('especialidades.estado', $especialidad))
        ->assertSessionHas('exito', fn ($mensaje) => str_contains($mensaje, '1 médico'));

    $this->assertDatabaseHas('especialidades', ['id' => $especialidad->id, 'activo' => false]);
});

it('permite a la recepcionista consultar pero no crear ni desactivar', function () {
    $especialidad = crearEspecialidad('Odontología');

    $this->actingAs($this->recepcion)->get('/especialidades')->assertOk()->assertSee('Odontología');
    $this->actingAs($this->recepcion)->post('/especialidades', ['nombre' => 'Nueva'])->assertForbidden();
    $this->actingAs($this->recepcion)->patch(route('especialidades.estado', $especialidad))->assertForbidden();
});
