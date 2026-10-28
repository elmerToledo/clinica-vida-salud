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

    /**
     * Menú lateral con grupos desplegables. Cada entrada es un enlace suelto (ruta) o un grupo (items).
     * Mismas opciones y permisos por rol que para(); solo cambia la forma de presentarlas.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function agrupado(User $usuario): array
    {
        $enlace = fn (string $etiqueta, string $ruta, string $icono, string $patron, array $excluir = []) => compact('etiqueta', 'ruta', 'icono', 'patron', 'excluir');
        $grupo = fn (string $etiqueta, string $icono, array $items) => compact('etiqueta', 'icono', 'items');

        $inicio = $enlace('Inicio', 'dashboard', 'home', 'dashboard');

        $listadoPacientes = $enlace('Listado de pacientes', 'pacientes.index', 'users', 'pacientes.*', ['pacientes.create']);
        $registrarPaciente = $enlace('Registrar paciente', 'pacientes.create', 'user-plus', 'pacientes.create');
        $pacientes = $grupo('Pacientes', 'users', [$listadoPacientes, $registrarPaciente]);

        $medicosYEspecialidades = $grupo('Médicos y especialidades', 'stethoscope', [
            $enlace('Médicos', 'medicos.index', 'stethoscope', 'medicos.*'),
            $enlace('Especialidades', 'especialidades.index', 'heart', 'especialidades.*'),
            $enlace('Horarios', 'horarios.index', 'clock', 'horarios.*'),
        ]);

        $opciones = match ($usuario->rol) {
            'administrador' => [
                $inicio,
                $enlace('Citas', 'citas.index', 'calendar-check', 'citas.*'),
                $pacientes,
                $medicosYEspecialidades,
                $grupo('Usuarios y acceso', 'shield-check', [
                    $enlace('Usuarios', 'usuarios.index', 'lock', 'usuarios.*', ['usuarios.create']),
                    $enlace('Crear usuario', 'usuarios.create', 'user-plus', 'usuarios.create'),
                    $enlace('Bitácora', 'bitacora.index', 'file-text', 'bitacora.*'),
                ]),
            ],
            'recepcionista' => [
                $inicio,
                $grupo('Citas médicas', 'calendar-check', [
                    $enlace('Agenda de citas', 'citas.index', 'calendar-check', 'citas.*', ['citas.disponibilidad', 'citas.create']),
                    $enlace('Nueva cita', 'citas.disponibilidad', 'calendar-plus', 'citas.disponibilidad'),
                ]),
                $pacientes,
                $medicosYEspecialidades,
            ],
            'medico' => [
                $inicio,
                $grupo('Mi consulta', 'stethoscope', [
                    $enlace('Mis horarios', 'horarios.index', 'clock', 'horarios.*'),
                    $enlace('Mi agenda', 'citas.index', 'calendar-check', 'citas.*'),
                ]),
            ],
            'paciente' => [
                $inicio,
                $grupo('Mis citas', 'calendar-check', [
                    $enlace('Reservar cita', 'citas.disponibilidad', 'calendar-plus', 'citas.*', ['mis-citas.*']),
                    $enlace('Mis citas', 'mis-citas.index', 'file-text', 'mis-citas.*'),
                ]),
                $enlace('Mis datos', 'perfil.edit', 'user-check', 'perfil.*'),
            ],
            default => [$inicio],
        };

        // Apariencia (tema y color) está disponible para todos los roles.
        $opciones[] = $enlace('Apariencia', 'apariencia', 'palette', 'apariencia');

        return $opciones;
    }
}
