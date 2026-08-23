<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Permisos por defecto por rol.
     * 'all' = todos los permisos definidos en Permiso::defaults().
     */
    private array $permisosPorRol = [
        'admin' => 'all',
        'director' => [
            'dashboard.ver',
            'alumnos.ver', 'alumnos.crear', 'alumnos.editar', 'alumnos.eliminar',
            'catedraticos.ver', 'catedraticos.crear', 'catedraticos.editar', 'catedraticos.eliminar',
            'cursos.ver', 'cursos.crear', 'cursos.editar', 'cursos.eliminar',
            'aulas.ver', 'aulas.crear', 'aulas.editar', 'aulas.eliminar',
            'grados.ver', 'grados.crear', 'grados.editar', 'grados.eliminar',
            'secciones.ver', 'secciones.crear', 'secciones.editar', 'secciones.eliminar',
            'edificios.ver', 'edificios.crear', 'edificios.editar', 'edificios.eliminar',
            'carreras.ver', 'carreras.crear', 'carreras.editar', 'carreras.eliminar',
            'periodos.ver', 'periodos.crear', 'periodos.editar', 'periodos.eliminar',
            'pensum.ver', 'pensum.crear', 'pensum.editar', 'pensum.eliminar',
            'asignaciones.ver', 'asignaciones.crear', 'asignaciones.editar', 'asignaciones.eliminar',
            'inscripciones.ver', 'inscripciones.crear', 'inscripciones.editar', 'inscripciones.eliminar',
            'horarios.ver', 'horarios.crear', 'horarios.editar', 'horarios.eliminar',
            'tareas.ver', 'tareas.crear', 'tareas.editar', 'tareas.eliminar',
            'evaluaciones.ver', 'evaluaciones.crear', 'evaluaciones.editar', 'evaluaciones.eliminar',
            'asistencias.ver', 'asistencias.registrar', 'asistencias.editar',
            'calificaciones.ver', 'calificaciones.registrar', 'calificaciones.editar',
            'entregas.ver', 'entregas.calificar',
            'archivos.ver', 'archivos.descargar',
            'notificaciones.ver', 'notificaciones.crear', 'notificaciones.eliminar',
            'bitacoras.ver',
            'reportes.ver', 'reportes.generar', 'reportes.descargar',
            'logs.ver',
        ],
        'secretaria' => [
            'dashboard.ver',
            'alumnos.ver', 'alumnos.crear', 'alumnos.editar',
            'catedraticos.ver', 'catedraticos.crear', 'catedraticos.editar',
            'aulas.ver',
            'asignaciones.ver', 'asignaciones.crear', 'asignaciones.editar',
            'inscripciones.ver', 'inscripciones.crear', 'inscripciones.editar', 'inscripciones.eliminar',
            'horarios.ver', 'horarios.crear', 'horarios.editar',
            'asistencias.ver', 'asistencias.registrar',
            'entregas.ver', 'entregas.subir',
            'archivos.ver', 'archivos.subir', 'archivos.descargar',
            'notificaciones.ver',
        ],
        'catedratico' => [
            'dashboard.ver',
            'asistencias.ver', 'asistencias.registrar',
            'calificaciones.ver', 'calificaciones.registrar', 'calificaciones.editar',
            'entregas.ver', 'entregas.calificar',
            'tareas.ver', 'tareas.crear', 'tareas.editar',
            'archivos.ver', 'archivos.subir', 'archivos.descargar',
            'notificaciones.ver', 'notificaciones.crear',
        ],
        'alumno' => [
            'dashboard.ver',
            'entregas.ver', 'entregas.subir',
            'archivos.ver', 'archivos.descargar',
            'notificaciones.ver',
        ],
    ];

    public function run(): void
    {
        $roles = [
            ['nombre' => 'admin', 'descripcion' => 'Administrador del sistema'],
            ['nombre' => 'director', 'descripcion' => 'Director académico'],
            ['nombre' => 'secretaria', 'descripcion' => 'Secretaría'],
            ['nombre' => 'catedratico', 'descripcion' => 'Catedrático / docente'],
            ['nombre' => 'alumno', 'descripcion' => 'Alumno / estudiante'],
        ];

        foreach ($roles as $rolData) {
            $rol = Rol::firstOrCreate(
                ['nombre' => $rolData['nombre']],
                $rolData
            );

            $nombres = $this->permisosPorRol[$rolData['nombre']] ?? [];

            if ($nombres === 'all') {
                $nombres = array_column(Permiso::defaults(), 'nombre');
            }

            $ids = Permiso::whereIn('nombre', $nombres)->pluck('id_permiso')->all();
            $rol->permisos()->sync($ids);
        }
    }
}
