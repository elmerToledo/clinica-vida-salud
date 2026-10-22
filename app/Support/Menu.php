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

        return match ($usuario->rol) {
            'administrador' => [$inicio, $usuarios, $pacientes, $medicos, $especialidades, $horarios],
            'recepcionista' => [$inicio, $pacientes, $medicos, $especialidades, $horarios],
            'medico' => [$inicio, array_merge($horarios, ['etiqueta' => 'Mis horarios', 'descripcion' => 'Sus días y horas de atención'])],
            default => [$inicio],
        };
    }
}
