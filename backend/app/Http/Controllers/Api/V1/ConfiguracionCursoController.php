<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Unidad;
use App\Traits\VerificaPropietarioCurso;
use Illuminate\Http\Request;

class ConfiguracionCursoController extends Controller
{
    use VerificaPropietarioCurso;

    /**
     * GET /v1/catedratico/configuracion-curso/{id_asignacion}
     * Datos consolidados del curso para la configuración del docente:
     * asignación, horarios, alumnos e unidades (módulos semanales) con sus tareas.
     */
    public function show(Request $request, $id_asignacion)
    {
        $asignacion = Asignacion::with([
            'curso',
            'grado',
            'seccion',
            'periodo',
            'aula',
            'horarios',
            'inscripciones.alumno',
            'evaluaciones',
            'zonas.evaluaciones',
            'materiales.archivo',
            'anuncios',
        ])->findOrFail($id_asignacion);

        $this->verificarCatedratico($request, $id_asignacion);

        $unidades = Unidad::where('id_asignacion', $id_asignacion)
            ->with('tareas.entregas')
            ->orderBy('numero_semana')
            ->get();

        return response()->json([
            'asignacion' => [
                'id_asignacion' => $asignacion->id_asignacion,
                'curso' => $asignacion->curso?->nombre_curso ?? 'Sin nombre',
                'codigo_curso' => $asignacion->curso?->codigo ?? ('CURSO-' . $asignacion->id_curso),
                'grado' => $asignacion->grado?->nombre ?? '-',
                'seccion' => $asignacion->seccion?->nombre ?? '-',
                'periodo' => $asignacion->periodo?->nombre ?? '-',
                'periodo_estado' => $asignacion->periodo?->estado ?? 'inactivo',
                'aula' => $asignacion->aula?->nombre_aula ?? '-',
                'total_alumnos' => $asignacion->inscripciones->count(),
            ],
            'horarios' => $asignacion->horarios->map(fn ($h) => [
                'id_horario' => $h->id_horario,
                'dia_semana' => $h->dia_semana,
                'hora_inicio' => $h->hora_inicio,
                'hora_fin' => $h->hora_fin,
            ])->values(),
            'alumnos' => $asignacion->inscripciones->map(fn ($i) => [
                'id_inscripcion' => $i->id_inscripcion,
                'id_alumno' => $i->alumno?->id_alumno,
                'nombre' => $i->alumno ? "{$i->alumno->nombre} {$i->alumno->apellido}" : '—',
                'codigo' => $i->alumno?->codigo_mineduc,
            ])->values(),
            'unidades' => $unidades->map(function ($u) use ($asignacion) {
                return [
                    'id_unidad' => $u->id_unidad,
                    'id_asignacion' => $u->id_asignacion,
                    'numero_semana' => $u->numero_semana,
                    'titulo' => $u->titulo,
                    'temas' => $u->temas,
                    'competencia' => $u->competencia,
                    'estado' => $u->estado,
                    'fecha_inicio' => $u->fecha_inicio?->toDateString(),
                    'fecha_fin' => $u->fecha_fin?->toDateString(),
                    'tareas' => $u->tareas->map(fn ($t) => [
                        'id_tarea' => $t->id_tarea,
                        'titulo' => $t->titulo,
                        'fecha_entrega' => $t->fecha_entrega,
                        'total_entregas' => $t->entregas->count(),
                        'total_alumnos' => $asignacion->inscripciones->count(),
                    ])->values(),
                ];
            })->values(),
            'evaluaciones' => $asignacion->evaluaciones->map(fn ($ev) => [
                'id_evaluacion' => $ev->id_evaluacion,
                'id_zona' => $ev->id_zona,
                'nombre' => $ev->nombre,
                'porcentaje' => $ev->porcentaje,
                'unidad_academica' => $ev->unidad_academica,
            ])->values(),
            'zonas' => $asignacion->zonas->map(function ($zona) {
                return [
                    'id_zona' => $zona->id_zona,
                    'nombre' => $zona->nombre,
                    'puntos' => (float) $zona->puntos,
                    'posicion' => $zona->posicion,
                    'evaluaciones' => $zona->evaluaciones->map(fn ($ev) => [
                        'id_evaluacion' => $ev->id_evaluacion,
                        'nombre' => $ev->nombre,
                        'porcentaje' => $ev->porcentaje,
                        'unidad_academica' => $ev->unidad_academica,
                    ])->values(),
                ];
            })->values(),
            'total_puntos_zonas' => (float) $asignacion->zonas->sum('puntos'),
            'materiales' => $asignacion->materiales->map(fn ($m) => [
                'id_material' => $m->id_material,
                'id_unidad' => $m->id_unidad,
                'titulo' => $m->titulo,
                'descripcion' => $m->descripcion,
                'tipo' => $m->tipo,
                'url' => $m->url,
                'id_archivo' => $m->id_archivo,
                'nombre_archivo' => $m->archivo?->nombre,
                'fecha_publicacion' => $m->fecha_publicacion,
            ])->values(),
            'anuncios' => $asignacion->anuncios->map(fn ($a) => [
                'id_anuncio' => $a->id_anuncio,
                'titulo' => $a->titulo,
                'contenido' => $a->contenido,
                'fecha_publicacion' => $a->fecha_publicacion,
            ])->values(),
        ]);
    }
}
