<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use LogsActivity;

    protected $table = 'permiso';
    protected $primaryKey = 'id_permiso';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'id_permiso', 'id_rol');
    }

    /**
     * Permisos por defecto del sistema, agrupados por módulo y acción.
     * Reutilizado por el seeder y por el endpoint POST /v1/permisos/seed.
     */
    public static function defaults(): array
    {
        $modulos = [
            'dashboard'     => ['ver'],
            'usuarios'      => ['ver', 'crear', 'editar', 'eliminar'],
            'roles'         => ['ver', 'crear', 'editar', 'eliminar', 'asignar'],
            'permisos'      => ['ver', 'crear', 'editar', 'eliminar', 'asignar'],
            'alumnos'       => ['ver', 'crear', 'editar', 'eliminar'],
            'catedraticos'  => ['ver', 'crear', 'editar', 'eliminar'],
            'cursos'        => ['ver', 'crear', 'editar', 'eliminar'],
            'aulas'         => ['ver', 'crear', 'editar', 'eliminar'],
            'grados'        => ['ver', 'crear', 'editar', 'eliminar'],
            'secciones'     => ['ver', 'crear', 'editar', 'eliminar'],
            'edificios'     => ['ver', 'crear', 'editar', 'eliminar'],
            'carreras'      => ['ver', 'crear', 'editar', 'eliminar'],
            'periodos'      => ['ver', 'crear', 'editar', 'eliminar'],
            'pensum'        => ['ver', 'crear', 'editar', 'eliminar'],
            'asignaciones'  => ['ver', 'crear', 'editar', 'eliminar'],
            'inscripciones' => ['ver', 'crear', 'editar', 'eliminar'],
            'horarios'      => ['ver', 'crear', 'editar', 'eliminar'],
            'tareas'        => ['ver', 'crear', 'editar', 'eliminar'],
            'evaluaciones'  => ['ver', 'crear', 'editar', 'eliminar'],
            'asistencias'   => ['ver', 'registrar', 'editar'],
            'calificaciones' => ['ver', 'registrar', 'editar'],
            'entregas'      => ['ver', 'calificar', 'subir'],
            'archivos'      => ['ver', 'subir', 'descargar', 'eliminar'],
            'notificaciones' => ['ver', 'crear', 'eliminar'],
            'bitacoras'     => ['ver'],
            'reportes'      => ['ver', 'generar', 'descargar'],
            'configuracion' => ['ver', 'editar'],
            'logs'          => ['ver', 'eliminar'],
        ];

        $permisos = [];
        foreach ($modulos as $modulo => $acciones) {
            $moduloLabel = ucfirst($modulo);
            foreach ($acciones as $accion) {
                $accionLabel = ucfirst($accion);
                $permisos[] = [
                    'nombre' => "{$modulo}.{$accion}",
                    'descripcion' => "{$accionLabel} {$moduloLabel}",
                ];
            }
        }

        return $permisos;
    }
}
