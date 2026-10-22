<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostración de la Clínica Santa Aurora.
 *
 * Ejecutar con:  php artisan db:seed --class=ClinicaSeeder
 *
 * Se puede ejecutar varias veces: no duplica registros.
 * Las cuentas usan una contraseña de PRUEBA; no se deben usar en producción.
 */
class ClinicaSeeder extends Seeder
{
    private const CLAVE_DEMO = 'Prueba2026Vida';

    public function run(): void
    {
        // ---------- Usuarios, uno por cada rol ----------
        $admin = $this->usuario('Admin Prueba', 'admin', 'admin@vidasalud.com', 'administrador');
        $this->usuario('Rosa Recepción', 'recepcion', 'recepcion@vidasalud.com', 'recepcionista');
        $userMedico = $this->usuario('Juan Pérez', 'medico', 'medico@vidasalud.com', 'medico');
        $userPaciente = $this->usuario('María López', 'paciente', 'paciente@vidasalud.com', 'paciente');

        // ---------- Especialidades ----------
        $nombres = [
            'Medicina general' => 'Atención médica integral y de primer contacto.',
            'Pediatría' => 'Atención médica de niños y adolescentes.',
            'Ginecología' => 'Salud de la mujer.',
            'Odontología' => 'Salud bucal y dental.',
            'Cardiología' => 'Enfermedades del corazón y del sistema circulatorio.',
        ];
        $especialidades = [];
        foreach ($nombres as $nombre => $descripcion) {
            $especialidades[$nombre] = Especialidad::firstOrCreate(
                ['nombre_normalizado' => Especialidad::normalizar($nombre)],
                ['nombre' => $nombre, 'descripcion' => $descripcion, 'activo' => true]
            );
        }

        // ---------- Médicos, con sus especialidades ----------
        $juan = $this->medico('RP-1001', 'Juan', 'Pérez', '70010001', 'juan.perez@vidasalud.com', $userMedico->id,
            [$especialidades['Medicina general']], true);
        $ana = $this->medico('RP-1002', 'Ana', 'Gutiérrez', '70010002', 'ana.gutierrez@vidasalud.com', null,
            [$especialidades['Pediatría'], $especialidades['Medicina general']]);
        $carlos = $this->medico('RP-1003', 'Carlos', 'Rojas', '70010003', 'carlos.rojas@vidasalud.com', null,
            [$especialidades['Odontología']]);

        // ---------- Horarios (1 = lunes ... 7 = domingo), citas de 30 minutos ----------
        $this->horario($juan, 1, '08:00:00', '12:00:00'); // lunes
        $this->horario($juan, 3, '14:00:00', '18:00:00'); // miércoles
        $this->horario($ana, 2, '09:00:00', '13:00:00');  // martes
        $this->horario($carlos, 4, '08:00:00', '12:00:00'); // jueves
        $this->horario($carlos, 5, '08:00:00', '12:00:00'); // viernes

        // ---------- Pacientes de ejemplo ----------
        $pacientes = [
            ['1234567', 'María', 'López', '1990-05-14', 'F', '71110001', 'paciente@vidasalud.com', $userPaciente->id],
            ['2345678', 'Pedro', 'Suárez', '1985-11-02', 'M', '71110002', null, null],
            ['3456789', 'Lucía', 'Fernández', '2015-03-20', 'F', '71110003', null, null],
            ['4567890', 'Jorge', 'Mamani', '1972-08-09', 'M', '71110004', null, null],
            ['5678901', 'Carla', 'Vargas', '2000-01-30', 'F', '71110005', null, null],
        ];
        foreach ($pacientes as [$ci, $nombre, $apellido, $nacimiento, $sexo, $telefono, $correo, $userId]) {
            Paciente::updateOrCreate(
                ['ci' => $ci],
                [
                    'user_id' => $userId,
                    'nombres' => $nombre,
                    'apellidos' => $apellido,
                    'fecha_nacimiento' => $nacimiento,
                    'sexo' => $sexo,
                    'telefono' => $telefono,
                    'email' => $correo,
                    'activo' => true,
                ]
            );
        }
    }

    /** Crea o actualiza un usuario con su rol y su nombre de usuario (se puede ingresar con usuario o con correo). */
    private function usuario(string $nombre, string $usuario, string $correo, string $rol): User
    {
        return User::unguarded(fn () => User::updateOrCreate(
            ['email' => $correo],
            [
                'name' => $nombre,
                'usuario' => $usuario,
                'password' => Hash::make(self::CLAVE_DEMO),
                'rol' => $rol,
                'activo' => true,
                'email_verified_at' => now(),
            ]
        ));
    }

    /** @param array<int, Especialidad> $especialidades */
    private function medico(string $registro, string $nombres, string $apellidos, string $telefono, string $correo, ?int $userId, array $especialidades, bool $puedeAtender = false): Medico
    {
        $medico = Medico::updateOrCreate(
            ['registro_profesional' => $registro],
            [
                'user_id' => $userId,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $telefono,
                'email' => $correo,
                'puede_marcar_atendida' => $puedeAtender,
                'activo' => true,
            ]
        );

        $medico->especialidades()->syncWithoutDetaching(array_map(fn (Especialidad $e) => $e->id, $especialidades));

        return $medico;
    }

    private function horario(Medico $medico, int $dia, string $inicio, string $fin): void
    {
        HorarioAtencion::firstOrCreate(
            ['medico_id' => $medico->id, 'dia_semana' => $dia, 'hora_inicio' => $inicio],
            ['hora_fin' => $fin, 'duracion_cita_min' => 30, 'activo' => true]
        );
    }
}
