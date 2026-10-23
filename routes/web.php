<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\HorarioAtencionController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Página pública de la clínica; quien ya tiene sesión pasa directo al inicio del sistema.
Route::get('/', LandingController::class)->name('inicio');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Mi cuenta: datos propios y cambio de contraseña (CU07).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // ---- Usuarios y roles (CU03-CU06): solo Administrador ----
    Route::middleware('rol:administrador')->group(function () {
        Route::resource('usuarios', UsuarioController::class)
            ->except(['show', 'destroy'])
            ->parameters(['usuarios' => 'usuario']);
        Route::patch('usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('usuarios.estado');
    });

    // ---- Bitácora del sistema: solo Administrador (solo consulta) ----
    Route::get('bitacora', [BitacoraController::class, 'index'])
        ->middleware('rol:administrador')
        ->name('bitacora.index');

    // ---- Pacientes (CU08-CU11): Administrador y Recepcionista; desactivar solo Administrador ----
    Route::middleware('rol:administrador,recepcionista')->group(function () {
        Route::resource('pacientes', PacienteController::class)
            ->except(['show', 'destroy'])
            ->parameters(['pacientes' => 'paciente']);
    });
    Route::patch('pacientes/{paciente}/estado', [PacienteController::class, 'cambiarEstado'])
        ->middleware('rol:administrador')
        ->name('pacientes.estado');

    // ---- Médicos (CU12-CU15): Recepcionista solo consulta (CU13) ----
    Route::resource('medicos', MedicoController::class)
        ->only(['index'])
        ->middleware('rol:administrador,recepcionista');
    Route::middleware('rol:administrador')->group(function () {
        Route::resource('medicos', MedicoController::class)
            ->only(['create', 'store', 'edit', 'update'])
            ->parameters(['medicos' => 'medico']);
        Route::patch('medicos/{medico}/estado', [MedicoController::class, 'cambiarEstado'])->name('medicos.estado');
    });

    // ---- Especialidades (CU16-CU19): Recepcionista solo consulta (CU17) ----
    Route::resource('especialidades', EspecialidadController::class)
        ->only(['index'])
        ->middleware('rol:administrador,recepcionista');
    Route::middleware('rol:administrador')->group(function () {
        Route::resource('especialidades', EspecialidadController::class)
            ->only(['create', 'store', 'edit', 'update'])
            ->parameters(['especialidades' => 'especialidad']);
        Route::patch('especialidades/{especialidad}/estado', [EspecialidadController::class, 'cambiarEstado'])->name('especialidades.estado');
    });

    // ---- Horarios de atención (CU20-CU23): consultan Administrador, Recepcionista y Médico ----
    Route::resource('horarios', HorarioAtencionController::class)
        ->only(['index'])
        ->middleware('rol:administrador,recepcionista,medico');
    Route::middleware('rol:administrador')->group(function () {
        Route::resource('horarios', HorarioAtencionController::class)
            ->only(['create', 'store', 'edit', 'update'])
            ->parameters(['horarios' => 'horario']);
        Route::patch('horarios/{horario}/estado', [HorarioAtencionController::class, 'cambiarEstado'])->name('horarios.estado');
    });

    // ---- Citas (CU24-CU32): disponibilidad la consultan Recepcionista y Paciente; registrar, solo Recepcionista ----
    Route::get('citas/disponibilidad', [CitaController::class, 'disponibilidad'])
        ->middleware('rol:recepcionista,paciente')
        ->name('citas.disponibilidad');
    Route::middleware('rol:recepcionista')->group(function () {
        Route::get('citas/nueva', [CitaController::class, 'create'])->name('citas.create');
        Route::post('citas', [CitaController::class, 'store'])->name('citas.store');
    });
    Route::get('citas', [CitaController::class, 'index'])
        ->middleware('rol:administrador,recepcionista,medico')
        ->name('citas.index');
    Route::get('citas/{cita}', [CitaController::class, 'show'])->whereNumber('cita')->name('citas.show');
    Route::middleware('rol:recepcionista')->group(function () {
        Route::get('citas/{cita}/reprogramar', [CitaController::class, 'reprogramarForm'])->whereNumber('cita')->name('citas.reprogramar');
        Route::patch('citas/{cita}/reprogramar', [CitaController::class, 'reprogramar'])->whereNumber('cita');
        Route::patch('citas/{cita}/confirmar', [CitaController::class, 'confirmar'])->whereNumber('cita')->name('citas.confirmar');
        Route::patch('citas/{cita}/asistencia', [CitaController::class, 'asistencia'])->whereNumber('cita')->name('citas.asistencia');
    });
    Route::patch('citas/{cita}/cancelar', [CitaController::class, 'cancelar'])
        ->middleware('rol:recepcionista,paciente')->whereNumber('cita')->name('citas.cancelar');
    Route::patch('citas/{cita}/atender', [CitaController::class, 'atender'])
        ->middleware('rol:medico')->whereNumber('cita')->name('citas.atender');
});

require __DIR__.'/auth.php';
