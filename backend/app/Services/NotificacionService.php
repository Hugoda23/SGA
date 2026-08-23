<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Usuario;

class NotificacionService
{
    /**
     * Crea una notificación para un usuario (modelo).
     */
    public static function crear(Usuario $usuario, string $mensaje): Notificacion
    {
        return Notificacion::create([
            'id_usuario' => $usuario->id_usuario,
            'mensaje' => $mensaje,
            'fecha' => now(),
            'leido' => false,
        ]);
    }

    /**
     * Crea una notificación para varios usuarios (modelos).
     */
    public static function crearMultiple(array $usuarios, string $mensaje): int
    {
        $count = 0;

        foreach ($usuarios as $usuario) {
            if ($usuario instanceof Usuario) {
                self::crear($usuario, $mensaje);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Crea una notificación para un usuario por su id.
     */
    public static function paraUsuario(?int $idUsuario, string $mensaje): void
    {
        if (!$idUsuario) {
            return;
        }

        Notificacion::create([
            'id_usuario' => $idUsuario,
            'mensaje' => $mensaje,
            'fecha' => now(),
            'leido' => false,
        ]);
    }

    /**
     * Notifica a todos los alumnos activos inscritos en una asignación.
     */
    public static function paraAlumnosDeAsignacion($asignacion, string $mensaje): void
    {
        if (!$asignacion || !method_exists($asignacion, 'inscripciones')) {
            return;
        }

        $asignacion->inscripciones()
            ->where('estado', 'activo')
            ->with('alumno')
            ->get()
            ->each(function ($inscripcion) use ($mensaje) {
                self::paraUsuario($inscripcion->alumno?->id_usuario, $mensaje);
            });
    }
}
