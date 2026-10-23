<?php

namespace App\Support;

use App\Models\User;

/**
 * Opciones de menú según el rol (RN12). Se usa en la barra de navegación y en el inicio.
 *
 * Cada opción: etiqueta, ruta (nombre de ruta), patron (para marcar la opción activa) y descripcion.
 */
class Menu
{
    /** @return array<int, array{etiqueta: string, ruta: string, patron: string, descripcion: string}> */
    public static function para(User $usuario): array
    {
        $inicio = ['etiqueta' => 'Inicio', 'ruta' => 'dashboard', 'patron' => 'dashboard', 'descripcion' => 'Resumen del sistema'];
        $usuarios = ['etiqueta' => 'Usuarios', 'ruta' => 'usuarios.index', 'patron' => 'usuarios.*', 'descripcion' => 'Cuentas y roles de acceso'];
        $pacientes = ['etiqueta' => 'Pacientes', 'ruta' => 'pacientes.index', 'patron' => 'pacientes.*', 'descripcion' => 'Registro y búsqueda de pacientes'];
        $medicos = ['etiqueta' => 'Médicos', 'ruta' => 'medicos.index', 'patron' => 'medicos.*', 'descripcion' => 'Médicos y sus especialidades'];
        $especialidades = ['etiqueta' => 'Especialidades', 'ruta' => 'especialidades.index', 'patron' => 'especialidades.*', 'descripcion' => 'Catálogo de especialidades médicas'];
        $horarios = ['etiqueta' => 'Horarios', 'ruta' => 'horarios.index', 'patron' => 'horarios.*', 'descripcion' => 'Horarios de atención de los médicos'];

        $citas = ['etiqueta' => 'Citas', 'ruta' => 'citas.index', 'patron' => 'citas.*', 'descripcion' => 'Agenda y registro de citas médicas'];

        $bitacora = ['etiqueta' => 'Bitácora', 'ruta' => 'bitacora.index', 'patron' => 'bitacora.*', 'descripcion' => 'Registro de actividad del sistema'];

        $reservar = ['etiqueta' => 'Reservar cita', 'ruta' => 'citas.disponibilidad', 'patron' => 'citas.*', 'descripcion' => 'Elija médico, fecha y horario'];
        $misCitas = ['etiqueta' => 'Mis citas', 'ruta' => 'mis-citas.index', 'patron' => 'mis-citas.*', 'descripcion' => 'Sus próximas citas y su historial'];
        $misDatos = ['etiqueta' => 'Mis datos', 'ruta' => 'perfil.edit', 'patron' => 'perfil.*', 'descripcion' => 'Sus datos de paciente'];

        return match ($usuario->rol) {
            'administrador' => [$inicio, $usuarios, $pacientes, $medicos, $especialidades, $horarios, array_merge($citas, ['descripcion' => 'Agenda de citas de todos los médicos']), $bitacora],
            'recepcionista' => [$inicio, $pacientes, $medicos, $especialidades, $horarios, $citas],
            'medico' => [$inicio, array_merge($horarios, ['etiqueta' => 'Mis horarios', 'descripcion' => 'Sus días y horas de atención']), array_merge($citas, ['etiqueta' => 'Mi agenda', 'descripcion' => 'Sus citas por fecha y estado'])],
            'paciente' => [$inicio, $reservar, $misCitas, $misDatos],
            default => [$inicio],
        };
    }
}
