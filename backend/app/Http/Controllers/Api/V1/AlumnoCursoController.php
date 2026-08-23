<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Asignacion;
use App\Models\EntregaTarea;
use App\Models\Tarea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlumnoCursoController extends Controller
{
    /**
     * GET /v1/alumno/mis-cursos
     * Cursos en los que el alumno autenticado está inscrito.
     */
    public function misCursos(Request $request)
    {
        $alumno = $request->user()->alumno;
        if (!$alumno) {
            return response()->json([], 200);
        }

        $inscripciones = $alumno->inscripciones()->with([
            'asignacion.curso',
            'asignacion.grado',
            'asignacion.seccion',
            'asignacion.periodo',
            'asignacion.catedratico',
            'asignacion.unidades',
            'asignacion.tareas',
            'asignacion.materiales',
            'asignacion.anuncios',
            'asignacion.evaluaciones',
        ])->get();

        return $inscripciones->map(function ($ins) {
            $asignacion = $ins->asignacion;
            if (!$asignacion) {
                return null;
            }

            $tareasPendientes = $asignacion->tareas->filter(function ($t) {
                return !$t->fecha_entrega || now()->lessThanOrEqualTo($t->fecha_entrega);
            })->count();

            return [
                'id_inscripcion' => $ins->id_inscripcion,
                'id_asignacion' => $asignacion->id_asignacion,
                'curso' => $asignacion->curso?->nombre_curso ?? 'Sin nombre',
                'codigo_curso' => $asignacion->curso?->codigo ?? ('CURSO-' . $asignacion->id_curso),
                'grado' => $asignacion->grado?->nombre ?? '-',
                'seccion' => $asignacion->seccion?->nombre ?? '-',
                'periodo' => $asignacion->periodo?->nombre ?? '-',
                'periodo_estado' => $asignacion->periodo?->estado ?? 'inactivo',
                'catedratico' => $asignacion->catedratico
                    ? "{$asignacion->catedratico->nombre} {$asignacion->catedratico->apellido}"
                    : '—',
                'total_unidades' => $asignacion->unidades->count(),
                'total_tareas' => $asignacion->tareas->count(),
                'tareas_pendientes' => $tareasPendientes,
                'total_materiales' => $asignacion->materiales->count(),
                'total_anuncios' => $asignacion->anuncios->count(),
                'total_evaluaciones' => $asignacion->evaluaciones->count(),
            ];
        })->filter()->values();
    }

    /**
     * GET /v1/alumno/resumen
     * Resumen del alumno autenticado para el dashboard "Mi Resumen":
     * promedio general, tareas pendientes, próxima entrega, asistencia y avisos.
     */
    public function resumen(Request $request)
    {
        $alumno = $request->user()->alumno;
        if (!$alumno) {
            return response()->json([
                'promedio_general' => null,
                'total_cursos' => 0,
                'tareas_pendientes' => 0,
                'proxima_entrega' => null,
                'asistencia_porcentaje' => null,
                'asistencias_registradas' => 0,
                'proximas_entregas' => [],
                'avisos' => [],
            ], 200);
        }

        $inscripciones = $alumno->inscripciones()->with([
            'asignacion.curso',
            'asignacion.tareas',
            'asignacion.anuncios',
            'asistencias',
            'calificacionesFinales',
        ])->get();

        $idTareas = $inscripciones
            ->flatMap(fn ($ins) => $ins->asignacion?->tareas->pluck('id_tarea'))
            ->unique();

        $entregas = EntregaTarea::where('id_alumno', $alumno->id_alumno)
            ->whereIn('id_tarea', $idTareas)
            ->get()
            ->keyBy('id_tarea');

        $promedioPorCurso = $inscripciones->map(function ($ins) {
            $notas = $ins->calificacionesFinales
                ->map(fn ($cf) => (float) $cf->nota_final)
                ->filter(fn ($n) => $n > 0);
            if ($notas->isEmpty()) {
                return null;
            }
            return $notas->avg();
        })->filter();

        $promedioGeneral = $promedioPorCurso->isNotEmpty()
            ? round($promedioPorCurso->avg(), 2)
            : null;

        $proximasEntregas = collect();
        foreach ($inscripciones as $ins) {
            foreach ($ins->asignacion?->tareas ?? collect() as $t) {
                $entrega = $entregas->get($t->id_tarea);
                $presentada = $entrega && $entrega->estado === 'entregada';
                if ($presentada) {
                    continue;
                }
                $proximasEntregas->push([
                    'id_tarea' => $t->id_tarea,
                    'titulo' => $t->titulo,
                    'curso' => $ins->asignacion?->curso?->nombre_curso ?? 'Asignación Académica',
                    'fecha_entrega' => $t->fecha_entrega?->format('Y-m-d H:i:s'),
                    'estado' => $entrega?->estado ?? null,
                ]);
            }
        }

        $proximasEntregas = $proximasEntregas->sortBy('fecha_entrega')->values();

        $proximaEntrega = $proximasEntregas
            ->first(fn ($t) => $t['fecha_entrega'] !== null)['fecha_entrega'] ?? null;

        $asistencias = $inscripciones->flatMap(fn ($ins) => $ins->asistencias);
        $totalAsistencias = $asistencias->count();
        $presentes = $asistencias->filter(fn ($a) => strtolower($a->estado) === 'presente')->count();

        $porcentajeAsistencia = $totalAsistencias > 0
            ? round($presentes / $totalAsistencias * 100)
            : null;

        $avisos = $inscripciones
            ->flatMap(function ($ins) {
                return ($ins->asignacion?->anuncios ?? collect())->map(fn ($an) => [
                    'id_anuncio' => $an->id_anuncio,
                    'titulo' => $an->titulo,
                    'contenido' => $an->contenido,
                    'curso' => $ins->asignacion?->curso?->nombre_curso ?? 'Asignación Académica',
                    'fecha_publicacion' => $an->fecha_publicacion?->format('Y-m-d H:i:s'),
                ]);
            })
            ->sortByDesc('fecha_publicacion')
            ->values()
            ->take(4);

        return response()->json([
            'promedio_general' => $promedioGeneral,
            'total_cursos' => $inscripciones->count(),
            'tareas_pendientes' => $proximasEntregas->count(),
            'proxima_entrega' => $proximaEntrega,
            'asistencia_porcentaje' => $porcentajeAsistencia,
            'asistencias_registradas' => $totalAsistencias,
            'proximas_entregas' => $proximasEntregas->take(4),
            'avisos' => $avisos,
        ]);
    }

    /**
     * GET /v1/alumno/curso/{id_asignacion}
     * Detalle completo del curso para el alumno.
     */
    public function show(Request $request, $id_asignacion)
    {
        $alumno = $request->user()->alumno;
        if (!$alumno || !$this->estaInscrito($alumno, $id_asignacion)) {
            return response()->json(['error' => 'No estás inscrito en este curso'], 403);
        }

        $asignacion = Asignacion::with([
            'curso',
            'grado',
            'seccion',
            'periodo',
            'aula',
            'catedratico',
            'horarios',
            'unidades.tareas',
            'tareas.unidad',
            'materiales.archivo',
            'anuncios',
            'evaluaciones',
            'zonas.evaluaciones',
        ])->findOrFail($id_asignacion);

        $tareasCurso = $asignacion->tareas;

        $entregas = EntregaTarea::where('id_alumno', $alumno->id_alumno)
            ->whereIn('id_tarea', $tareasCurso->pluck('id_tarea'))
            ->get()
            ->keyBy('id_tarea');

        return response()->json([
            'asignacion' => [
                'id_asignacion' => $asignacion->id_asignacion,
                'curso' => $asignacion->curso?->nombre_curso ?? 'Sin nombre',
                'codigo_curso' => $asignacion->curso?->codigo ?? ('CURSO-' . $asignacion->id_curso),
                'grado' => $asignacion->grado?->nombre ?? '-',
                'seccion' => $asignacion->seccion?->nombre ?? '-',
                'periodo' => $asignacion->periodo?->nombre ?? '-',
                'aula' => $asignacion->aula?->nombre_aula ?? '-',
                'catedratico' => $asignacion->catedratico
                    ? "{$asignacion->catedratico->nombre} {$asignacion->catedratico->apellido}"
                    : '—',
            ],
            'horarios' => $asignacion->horarios->map(fn ($h) => [
                'id_horario' => $h->id_horario,
                'dia_semana' => $h->dia_semana,
                'hora_inicio' => $h->hora_inicio,
                'hora_fin' => $h->hora_fin,
            ])->values(),
            'unidades' => $asignacion->unidades->map(function ($u) use ($entregas) {
                return [
                    'id_unidad' => $u->id_unidad,
                    'numero_semana' => $u->numero_semana,
                    'titulo' => $u->titulo,
                    'temas' => $u->temas,
                    'competencia' => $u->competencia,
                    'estado' => $u->estado,
                    'fecha_inicio' => $u->fecha_inicio?->toDateString(),
                    'fecha_fin' => $u->fecha_fin?->toDateString(),
                    'tareas' => $u->tareas->map(function ($t) use ($entregas) {
                        $entrega = $entregas->get($t->id_tarea);
                        return [
                            'id_tarea' => $t->id_tarea,
                            'titulo' => $t->titulo,
                            'descripcion' => $t->descripcion,
                            'puntos' => $t->puntos,
                            'fecha_entrega' => $t->fecha_entrega,
                            'permitir_link' => $t->permitir_link,
                            'mi_entrega' => $entrega ? [
                                'id_entrega' => $entrega->id_entrega,
                                'archivo' => $entrega->archivo,
                                'nombre_original' => $entrega->nombre_original,
                                'link' => $entrega->link,
                                'fecha_entrega' => $entrega->fecha_entrega,
                                'calificacion' => $entrega->calificacion,
                                'estado' => $entrega->estado,
                            ] : null,
                        ];
                    })->values(),
                ];
            })->values(),
            'tareas' => $tareasCurso->map(function ($t) use ($entregas) {
                $entrega = $entregas->get($t->id_tarea);
                return [
                    'id_tarea' => $t->id_tarea,
                    'id_unidad' => $t->id_unidad,
                    'titulo' => $t->titulo,
                    'descripcion' => $t->descripcion,
                    'puntos' => $t->puntos,
                    'fecha_entrega' => $t->fecha_entrega,
                    'permitir_link' => $t->permitir_link,
                    'unidad' => $t->unidad ? [
                        'id_unidad' => $t->unidad->id_unidad,
                        'numero_semana' => $t->unidad->numero_semana,
                        'titulo' => $t->unidad->titulo,
                    ] : null,
                    'mi_entrega' => $entrega ? [
                        'id_entrega' => $entrega->id_entrega,
                        'archivo' => $entrega->archivo,
                        'nombre_original' => $entrega->nombre_original,
                        'link' => $entrega->link,
                        'fecha_entrega' => $entrega->fecha_entrega,
                        'calificacion' => $entrega->calificacion,
                        'estado' => $entrega->estado,
                    ] : null,
                ];
            })->values(),
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
                    'evaluaciones' => $zona->evaluaciones->map(fn ($ev) => [
                        'id_evaluacion' => $ev->id_evaluacion,
                        'nombre' => $ev->nombre,
                        'porcentaje' => $ev->porcentaje,
                        'unidad_academica' => $ev->unidad_academica,
                    ])->values(),
                ];
            })->values(),
        ]);
    }

    /**
     * POST /v1/alumno/curso/{id_asignacion}/entregar/{id_tarea}
     * Sube la entrega de una tarea validando la inscripción del alumno autenticado.
     *
     * Nota: la lógica de subida es casi idéntica a
     * EntregaTareaController::subirArchivo — existen ambos endpoints porque
     * el frontend los usa desde dos pantallas distintas (vista de un curso
     * vs. "Mis Tareas" global). Si se toca la validación de archivos o el
     * manejo de link/estado en uno, revisar también el otro.
     */
    public function entregarTarea(Request $request, $id_asignacion, $id_tarea)
    {
        $alumno = $request->user()->alumno;
        if (!$alumno || !$this->estaInscrito($alumno, $id_asignacion)) {
            return response()->json(['error' => 'No estás inscrito en este curso'], 403);
        }

        $tarea = Tarea::where('id_tarea', $id_tarea)
            ->where('id_asignacion', $id_asignacion)
            ->first();

        if (!$tarea) {
            return response()->json(['error' => 'La tarea no pertenece a este curso'], 404);
        }

        $datos = [];

        if ($request->hasFile('archivo')) {
            $request->validate([
                'archivo' => 'required|file|max:20480|mimetypes:application/pdf,application/zip,application/x-rar-compressed,application/vnd.rar,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.oasis.opendocument.text,text/plain,image/jpeg,image/png,image/gif',
            ]);

            $file = $request->file('archivo');
            $originalName = $file->getClientOriginalName();
            $path = $file->store('entregas', 'public');

            // Si reemplaza un archivo, limpiamos el enlace anterior
            $datos = ['archivo' => $path, 'nombre_original' => $originalName, 'link' => null];
        } else {
            $request->validate(['link' => 'required|url|max:500']);

            if (!$tarea->permitir_link) {
                return response()->json(['error' => 'Esta tarea no acepta entregas por enlace'], 422);
            }

            // Si reemplaza un archivo por un enlace, limpiamos el archivo anterior
            $datos = ['link' => $request->link, 'archivo' => null, 'nombre_original' => null];
        }

        $datos['estado'] = 'borrador';
        $datos['fecha_entrega'] = null;

        $entrega = EntregaTarea::updateOrCreate(
            ['id_tarea' => $id_tarea, 'id_alumno' => $alumno->id_alumno],
            $datos
        );

        return response()->json([
            'id_entrega' => $entrega->id_entrega,
            'archivo' => $entrega->archivo,
            'nombre_original' => $entrega->nombre_original,
            'link' => $entrega->link,
            'fecha_entrega' => $entrega->fecha_entrega,
            'estado' => $entrega->estado,
        ], 201);
    }

    /**
     * GET /v1/alumno/horario
     * Horario semanal del alumno autenticado: clases de sus inscripciones activas.
     */
    public function horario(Request $request)
    {
        $alumno = $request->user()->alumno;
        if (!$alumno) {
            return response()->json([], 200);
        }

        $inscripciones = $alumno->inscripciones()
            ->where('estado', 'activo')
            ->with(['asignacion.curso', 'asignacion.aula', 'asignacion.grado', 'asignacion.seccion', 'asignacion.periodo', 'asignacion.horarios'])
            ->get();

        $clases = $inscripciones->flatMap(function ($ins) {
            $asignacion = $ins->asignacion;
            if (!$asignacion) {
                return collect();
            }

            return $asignacion->horarios->map(fn ($h) => [
                'dia_semana' => $h->dia_semana,
                'hora_inicio' => substr((string) $h->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $h->hora_fin, 0, 5),
                'curso' => $asignacion->curso?->nombre_curso ?? 'Asignación académica',
                'codigo_curso' => $asignacion->curso?->codigo ?? '-',
                'aula' => $asignacion->aula?->nombre_aula ?? '-',
                'grado' => $asignacion->grado?->nombre ?? '-',
                'seccion' => $asignacion->seccion?->nombre ?? '-',
                'periodo' => $asignacion->periodo?->nombre ?? '-',
            ]);
        })->values();

        return response()->json($clases);
    }

    private function estaInscrito(Alumno $alumno, $id_asignacion): bool
    {
        return $alumno->inscripciones()
            ->where('id_asignacion', $id_asignacion)
            ->exists();
    }
}
