<?php

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Models\Paciente;
use App\Services\CitaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Todas las pruebas de la carpeta Feature usan TestCase y una base de datos
| limpia en cada prueba (RefreshDatabase). La configuración de pruebas está en
| phpunit.xml (base SQLite en memoria; no toca PostgreSQL).
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Funciones de apoyo
|--------------------------------------------------------------------------
|
| Crean datos de ejemplo directamente en la base para preparar cada prueba.
|
*/

/** Crea un paciente de prueba; los datos pueden sobrescribirse con $extra. */
function crearPaciente(array $extra = []): Paciente
{
    static $n = 0;
    $n++;

    return Paciente::create(array_merge([
        'ci' => (string) (1000000 + $n),
        'nombres' => 'Paciente',
        'apellidos' => 'Prueba' . $n,
        'fecha_nacimiento' => '1990-01-15',
        'sexo' => 'F',
        'telefono' => '71110000',
        'activo' => true,
    ], $extra));
}

/** Crea una especialidad de prueba. */
function crearEspecialidad(string $nombre = 'Medicina general', bool $activa = true): Especialidad
{
    return Especialidad::create(['nombre' => $nombre, 'activo' => $activa]);
}

/** Crea un médico de prueba con las especialidades indicadas (instancias de Especialidad). */
function crearMedico(array $extra = [], array $especialidades = []): Medico
{
    static $n = 0;
    $n++;

    $medico = Medico::create(array_merge([
        'registro_profesional' => 'RP-' . (5000 + $n),
        'nombres' => 'Medico',
        'apellidos' => 'Prueba' . $n,
        'activo' => true,
    ], $extra));

    if ($especialidades) {
        $medico->especialidades()->sync(array_map(fn ($e) => $e->id, $especialidades));
    }

    return $medico;
}

/** Crea un horario de atención de prueba (dia: 1 = lunes ... 7 = domingo). */
function crearHorario(Medico $medico, int $dia, string $inicio, string $fin, int $duracion = 30, bool $activo = true): HorarioAtencion
{
    return HorarioAtencion::create([
        'medico_id' => $medico->id,
        'dia_semana' => $dia,
        'hora_inicio' => $inicio,
        'hora_fin' => $fin,
        'duracion_cita_min' => $duracion,
        'activo' => $activo,
    ]);
}

// ---- Ciclo 2: ayudas para las pruebas de citas ----

function proximoLunes(): string
{
    return CarbonImmutable::now(CitaService::ZONA)->next(CarbonImmutable::MONDAY)->toDateString();
}

function datosCita($t, array $extra = []): array
{
    return array_merge([
        'paciente_id' => $t->paciente->id,
        'medico_id' => $t->medico->id,
        'especialidad_id' => $t->especialidad->id,
        'fecha' => proximoLunes(),
        'hora_inicio' => '09:00',
        'motivo_consulta' => 'Control',
    ], $extra);
}

function crearCita($t, array $extra = []): Cita
{
    return Cita::create(array_merge([
        'paciente_id' => $t->paciente->id,
        'medico_id' => $t->medico->id,
        'especialidad_id' => $t->especialidad->id,
        'fecha' => proximoLunes(),
        'hora_inicio' => '09:00',
        'hora_fin' => '09:30',
        'estado' => 'programada',
    ], $extra));
}
