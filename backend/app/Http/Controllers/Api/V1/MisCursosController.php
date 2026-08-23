<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Catedratico;
use Illuminate\Http\Request;

class MisCursosController extends Controller
{
    /**
     * Retorna las asignaciones del catedrático autenticado.
     */
    public function index(Request $request)
    {
        $usuario = $request->user();

        $catedratico = Catedratico::where('id_usuario', $usuario->id_usuario)->first();

        if (!$catedratico) {
            return response()->json(['error' => 'No se encontró perfil de catedrático'], 404);
        }

        $asignaciones = Asignacion::with([
            'curso',
            'aula',
            'periodo',
            'grado',
            'seccion',
            'tareas',
            'inscripciones',
            'horarios',
        ])
            ->where('id_catedratico', $catedratico->id_catedratico)
            ->get()
            ->map(function ($asignacion) {
                // Próxima clase: tomamos el primer horario disponible
                $proximoHorario = $asignacion->horarios->first();

                // Tareas pendientes (sin fecha_entrega o fecha futura)
                $tareasPendientes = $asignacion->tareas->filter(function ($tarea) {
                    return !isset($tarea->estado) || $tarea->estado !== 'cerrada';
                })->count();

                return [
                    'id_asignacion'    => $asignacion->id_asignacion,
                    'codigo_curso'     => $asignacion->curso?->codigo ?? ('CURSO-' . $asignacion->id_curso),
                    'nombre_curso'     => $asignacion->curso?->nombre_curso ?? 'Sin nombre',
                    'grado'            => $asignacion->grado?->nombre ?? ($asignacion->grado ?? '-'),
                    'seccion'          => $asignacion->seccion?->nombre ?? ($asignacion->seccion ?? '-'),
                    'aula'             => $asignacion->aula?->nombre_aula ?? '-',
                    'periodo'          => $asignacion->periodo?->nombre ?? '-',
                    'periodo_estado'   => $asignacion->periodo?->estado ?? 'inactivo',
                    'proximo_horario'  => $proximoHorario ? [
                        'dia'   => $proximoHorario->dia_semana ?? null,
                        'hora'  => $proximoHorario->hora_inicio ?? null,
                    ] : null,
                    'tareas_pendientes' => $tareasPendientes,
                    'total_inscritos'   => $asignacion->inscripciones->count(),
                ];
            });

        return response()->json([
            'catedratico' => [
                'nombre'      => $catedratico->nombre . ' ' . $catedratico->apellido,
                'especialidad' => $catedratico->especialidad,
                'codigo'      => $catedratico->codigo,
            ],
            'asignaciones' => $asignaciones,
        ]);
    }

    /**
     * Retorna las asignaciones del catedrático autenticado junto con
     * todas sus tareas asignadas y las estadísticas de entrega.
     */
    public function tareasPorCurso(Request $request)
    {
        $usuario = $request->user();

        $catedratico = Catedratico::where('id_usuario', $usuario->id_usuario)->first();

        if (!$catedratico) {
            return response()->json(['error' => 'No se encontró perfil de catedrático'], 404);
        }

        $asignaciones = Asignacion::with([
            'curso',
            'periodo',
            'grado',
            'seccion',
            'tareas.entregas' => fn ($q) => $q->where('estado', 'entregada'),
            'inscripciones',
        ])
            ->where('id_catedratico', $catedratico->id_catedratico)
            ->orderBy('id_periodo', 'desc')
            ->get()
            ->map(function ($asignacion) {
                $totalAlumnos = $asignacion->inscripciones->count();

                $tareas = $asignacion->tareas->map(function ($tarea) use ($totalAlumnos) {
                    $entregadas = $tarea->entregas;
                    $sinCalificar = $entregadas->filter(fn ($e) => $e->calificacion === null)->count();

                    return [
                        'id_tarea'        => $tarea->id_tarea,
                        'titulo'          => $tarea->titulo,
                        'descripcion'     => $tarea->descripcion,
                        'fecha_entrega'   => $tarea->fecha_entrega,
                        'total_entregas'  => $entregadas->count(),
                        'total_alumnos'   => $totalAlumnos,
                        'sin_calificar'   => $sinCalificar,
                    ];
                });

                return [
                    'id_asignacion'   => $asignacion->id_asignacion,
                    'codigo_curso'    => $asignacion->curso?->codigo ?? ('CURSO-' . $asignacion->id_curso),
                    'nombre_curso'    => $asignacion->curso?->nombre_curso ?? 'Sin nombre',
                    'grado'           => $asignacion->grado?->nombre ?? '-',
                    'seccion'         => $asignacion->seccion?->nombre ?? '-',
                    'periodo'         => $asignacion->periodo?->nombre ?? '-',
                    'periodo_estado'  => $asignacion->periodo?->estado ?? 'inactivo',
                    'total_alumnos'   => $totalAlumnos,
                    'tareas'          => $tareas->values(),
                ];
            });

        return response()->json([
            'asignaciones' => $asignaciones->values(),
        ]);
    }
}
