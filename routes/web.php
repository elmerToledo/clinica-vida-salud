<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\BloqueoController;
use App\Http\Controllers\HistorialMedicoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\RecordatorioController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\HorarioAtencionController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\MisCitasController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PerfilPacienteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Página pública de la clínica; quien ya tiene sesión pasa directo al inicio del sistema.
Route::get('/', LandingController::class)->name('inicio');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Apariencia: tema claro/oscuro y color de acento (se guardan en el navegador).
    Route::view('/apariencia', 'apariencia')->name('apariencia');

    // Médicos y sus horarios de atención, a simple vista (para todos los roles).
    Route::get('/directorio', [\App\Http\Controllers\DirectorioController::class, 'index'])->name('directorio.index');

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

    // ---- Historial de citas de un paciente (CU32): Administrador y Recepcionista ----
    Route::get('pacientes/{paciente}/historial', [CitaController::class, 'historialPaciente'])
        ->middleware('rol:administrador,recepcionista')->whereNumber('paciente')->name('pacientes.historial');

    // ---- Perfil del paciente (CU34, CU36): cada paciente ve solo lo propio ----
    Route::middleware('rol:paciente')->group(function () {
        Route::get('mis-citas', [MisCitasController::class, 'index'])->name('mis-citas.index');
        Route::get('mi-perfil', [PerfilPacienteController::class, 'edit'])->name('perfil.edit');
        Route::put('mi-perfil', [PerfilPacienteController::class, 'update'])->name('perfil.update');
    });

    // ---- Historial médico (CU44): el paciente ve el suyo; el médico, el de los pacientes que atendió ----
    Route::get('historial-medico', [HistorialMedicoController::class, 'mio'])->middleware('rol:paciente')->name('historial.mio');
    Route::middleware('rol:medico')->group(function () {
        Route::get('mis-pacientes', [HistorialMedicoController::class, 'pacientes'])->name('historial.pacientes');
        Route::get('mis-pacientes/{paciente}', [HistorialMedicoController::class, 'paciente'])->whereNumber('paciente')->name('historial.paciente');
    });

    // ---- Recordatorios (CU43): recepción y administrador; la campanita la ve cada usuario ----
    Route::middleware('rol:administrador,recepcionista')->group(function () {
        Route::get('recordatorios', [RecordatorioController::class, 'index'])->name('recordatorios.index');
        Route::post('recordatorios', [RecordatorioController::class, 'enviar'])->name('recordatorios.enviar');
    });
    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/leer-todas', [NotificacionController::class, 'leerTodas'])->name('notificaciones.leer-todas');
    Route::patch('notificaciones/{id}/leer', [NotificacionController::class, 'leer'])->name('notificaciones.leer');

    // ---- Feriados y bloqueos (CU46): solo administrador ----
    Route::middleware('rol:administrador')->group(function () {
        Route::resource('bloqueos', BloqueoController::class)->only(['index', 'create', 'store']);
        Route::patch('bloqueos/{bloqueo}/estado', [BloqueoController::class, 'cambiarEstado'])->name('bloqueos.estado');
    });

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

    // ---- Citas (CU24-CU32): disponibilidad, registro y reserva en línea (Recepcionista y Paciente) ----
    Route::get('citas/disponibilidad', [CitaController::class, 'disponibilidad'])
        ->middleware('rol:recepcionista,paciente')
        ->name('citas.disponibilidad');
    Route::get('citas/paciente', [CitaController::class, 'elegirPaciente'])
        ->middleware('rol:recepcionista')
        ->name('citas.paciente');
    Route::middleware('rol:recepcionista,paciente')->group(function () {
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
        // CU49: cobro de la cita en recepción.
        Route::post('citas/{cita}/cobro', [PagoController::class, 'store'])->whereNumber('cita')->name('citas.cobro');
    });
    Route::patch('citas/{cita}/cancelar', [CitaController::class, 'cancelar'])
        ->middleware('rol:recepcionista,paciente')->whereNumber('cita')->name('citas.cancelar');
    Route::patch('citas/{cita}/atender', [CitaController::class, 'atender'])
        ->middleware('rol:medico')->whereNumber('cita')->name('citas.atender');

    // ---- Reportes (CU37-CU42): solo consulta. Recepción: CU37, CU38 y CU40; administrador: los seis ----
    Route::prefix('reportes')->name('reportes.')->group(function () {
        // CU45: exportar a CSV; el controlador revisa qué reportes puede ver cada rol.
        Route::get('{reporte}/exportar', [ReporteController::class, 'exportar'])
            ->middleware('rol:administrador,recepcionista')
            ->where('reporte', 'fecha|medico|estado|especialidad|pacientes|periodo')->name('exportar');
        Route::middleware('rol:administrador,recepcionista')->group(function () {
            Route::get('/', [ReporteController::class, 'index'])->name('index');
            Route::get('citas-por-fecha', [ReporteController::class, 'porFecha'])->name('fecha');
            Route::get('citas-por-medico', [ReporteController::class, 'porMedico'])->name('medico');
            Route::get('citas-por-estado', [ReporteController::class, 'porEstado'])->name('estado');
        });
        Route::middleware('rol:administrador')->group(function () {
            Route::get('citas-por-especialidad', [ReporteController::class, 'porEspecialidad'])->name('especialidad');
            Route::get('pacientes-registrados', [ReporteController::class, 'pacientes'])->name('pacientes');
            Route::get('atendidas-canceladas', [ReporteController::class, 'periodo'])->name('periodo');
        });
    });
});

// Constancia de correos enviados: página aparte, sin enlace en el menú. Solo administrador, por URL directa.
Route::get('constancia-correos', [\App\Http\Controllers\ConstanciaCorreosController::class, 'index'])
    ->middleware(['auth', 'rol:administrador'])
    ->name('constancia.correos');

require __DIR__.'/auth.php';
