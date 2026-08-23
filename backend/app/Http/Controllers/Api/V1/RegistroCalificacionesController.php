<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\DetalleCalificacion;
use App\Models\CalificacionFinal;
use App\Models\Evaluacion;
use App\Models\ZonaEvaluacion;
use App\Services\CalificacionService;
use App\Traits\VerificaPropietarioCurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistroCalificacionesController extends Controller
{
    use VerificaPropietarioCurso;

    /**
     * GET /v1/registro-calificaciones/{id_asignacion}
     * Devuelve el cuadro completo: evaluaciones + alumnos inscritos + sus notas.
     */
    public function show(Request $request, $id_asignacion)
    {
        $asignacion = Asignacion::with([
            'curso',
            'grado',
            'seccion',
            'periodo',
            'evaluaciones',
            'zonas.evaluaciones',
            'inscripciones.alumno',
            'inscripciones.detalleCalificaciones',
            'inscripciones.calificacionesFinales',
        ])->findOrFail($id_asignacion);

        $this->verificarCatedratico($request, $id_asignacion);

        // Evaluaciones (columnas del cuadro)
        $evaluaciones = $asignacion->evaluaciones->map(fn($ev) => [
            'id_evaluacion'   => $ev->id_evaluacion,
            'id_zona'         => $ev->id_zona,
            'nombre'          => $ev->nombre,
            'porcentaje'      => $ev->porcentaje,
            'unidad_academica' => $ev->unidad_academica,
        ]);

        // Zonas de evaluación con sus actividades
        $zonas = $asignacion->zonas->map(function ($zona) use ($asignacion) {
            return [
                'id_zona'     => $zona->id_zona,
                'nombre'      => $zona->nombre,
                'puntos'      => (float) $zona->puntos,
                'posicion'    => $zona->posicion,
                'evaluaciones' => $zona->evaluaciones->map(fn($ev) => [
                    'id_evaluacion'   => $ev->id_evaluacion,
                    'nombre'          => $ev->nombre,
                    'porcentaje'      => $ev->porcentaje,
                    'unidad_academica' => $ev->unidad_academica,
                ])->values(),
            ];
        })->values();

        // Actividades sin zona asignada
        $evaluacionesSinZona = $asignacion->evaluaciones->whereNull('id_zona')->map(fn($ev) => [
            'id_evaluacion'   => $ev->id_evaluacion,
            'nombre'          => $ev->nombre,
            'porcentaje'      => $ev->porcentaje,
            'unidad_academica' => $ev->unidad_academica,
        ])->values();

        $totalPuntosZonas = $asignacion->zonas->sum('puntos');

        // Alumnos con sus notas por evaluación
        $alumnos = $asignacion->inscripciones->map(function ($inscripcion) use ($asignacion) {
            $notas = [];
            foreach ($asignacion->evaluaciones as $ev) {
                $detalle = $inscripcion->detalleCalificaciones
                    ->firstWhere('id_evaluacion', $ev->id_evaluacion);
                $notas[$ev->id_evaluacion] = [
                    'id_detalle' => $detalle?->id_detalle,
                    'nota'       => $detalle?->nota,
                ];
            }

            $calFinal = $inscripcion->calificacionesFinales->first();

            return [
                'id_inscripcion' => $inscripcion->id_inscripcion,
                'alumno'         => [
                    'id_alumno' => $inscripcion->alumno?->id_alumno,
                    'nombre'    => $inscripcion->alumno?->nombre . ' ' . $inscripcion->alumno?->apellido,
                    'codigo'    => $inscripcion->alumno?->codigo_mineduc,
                ],
                'notas'          => $notas,
                'nota_final'     => $calFinal?->nota_final,
                'id_calificacion' => $calFinal?->id_calificacion,
            ];
        });

        return response()->json([
            'asignacion' => [
                'id_asignacion' => $asignacion->id_asignacion,
                'curso'         => $asignacion->curso?->nombre_curso,
                'codigo_curso'  => $asignacion->curso?->codigo,
                'grado'         => $asignacion->grado?->nombre ?? $asignacion->grado,
                'seccion'       => $asignacion->seccion?->nombre ?? $asignacion->seccion,
                'periodo'       => $asignacion->periodo?->nombre,
            ],
            'evaluaciones' => $evaluaciones,
            'zonas'        => $zonas,
            'evaluaciones_sin_zona' => $evaluacionesSinZona,
            'total_puntos_zonas' => (float) $totalPuntosZonas,
            'alumnos'      => $alumnos,
        ]);
    }

    /**
     * POST /v1/registro-calificaciones/{id_asignacion}/guardar
     * Guarda/actualiza notas en masa.
     * Body: { notas: [ { id_inscripcion, id_evaluacion, nota } ] }
     */
    public function guardar(Request $request, $id_asignacion)
    {
        $asignacion = Asignacion::with('periodo')->findOrFail($id_asignacion);

        $this->verificarCatedratico($request, $id_asignacion);

        $bloqueado = $this->verificarPeriodoCerrado($asignacion);
        if ($bloqueado) {
            return $bloqueado;
        }

        $request->validate([
            'notas'                    => 'required|array',
            'notas.*.id_inscripcion'   => 'required|exists:inscripcion,id_inscripcion',
            'notas.*.id_evaluacion'    => 'required|exists:evaluacion,id_evaluacion',
            'notas.*.nota'             => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->notas as $item) {
                DetalleCalificacion::updateOrCreate(
                    [
                        'id_evaluacion'  => $item['id_evaluacion'],
                        'id_inscripcion' => $item['id_inscripcion'],
                    ],
                    ['nota' => $item['nota']]
                );
            }

            // Recalcular nota final para cada inscripción afectada
            $inscripciones = collect($request->notas)
                ->pluck('id_inscripcion')
                ->unique();

            CalificacionService::recalcularParaInscripciones($inscripciones->all());
        });

        return response()->json(['message' => 'Calificaciones guardadas correctamente.']);
    }

    /**
     * POST /v1/registro-calificaciones/{id_asignacion}/evaluaciones
     * Crea una nueva columna de evaluación para el curso.
     */
    public function crearEvaluacion(Request $request, $id_asignacion)
    {
        $asignacion = Asignacion::with('periodo')->findOrFail($id_asignacion);

        $this->verificarCatedratico($request, $id_asignacion);

        $bloqueado = $this->verificarPeriodoCerrado($asignacion);
        if ($bloqueado) {
            return $bloqueado;
        }

        $request->validate([
            'id_zona'         => 'nullable|exists:zona_evaluacion,id_zona',
            'nombre'          => 'required|string|max:100',
            'porcentaje'      => 'nullable|numeric|min:0|max:100',
            'unidad_academica' => 'nullable|integer',
        ]);

        if ($request->id_zona) {
            $zona = ZonaEvaluacion::where('id_zona', $request->id_zona)
                ->where('id_asignacion', $id_asignacion)
                ->first();
            if (!$zona) {
                return response()->json(['errors' => ['id_zona' => ['La zona no pertenece a este curso.']]], 422);
            }

            $disponible = $zona->puntosDisponibles();
            if ((float) ($request->porcentaje ?? 0) > $disponible) {
                return response()->json([
                    'message' => "La zona \"{$zona->nombre}\" solo tiene {$disponible} pts disponibles.",
                    'errors' => ['porcentaje' => ["La zona \"{$zona->nombre}\" solo tiene {$disponible} pts disponibles."]],
                ], 422);
            }
        }

        $evaluacion = Evaluacion::create([
            'id_asignacion'   => $id_asignacion,
            'id_zona'         => $request->id_zona,
            'nombre'          => $request->nombre,
            'porcentaje'      => $request->porcentaje,
            'unidad_academica' => $request->unidad_academica,
        ]);

        CalificacionService::recalcularNotasFinales(Asignacion::with(['zonas', 'evaluaciones'])->find($id_asignacion));

        return response()->json($evaluacion, 201);
    }

    /**
     * DELETE /v1/registro-calificaciones/evaluaciones/{id_evaluacion}
     * Elimina una columna de evaluación y sus detalles.
     */
    public function eliminarEvaluacion(Request $request, $id_evaluacion)
    {
        $evaluacion = Evaluacion::findOrFail($id_evaluacion);
        $id_asignacion = $evaluacion->id_asignacion;

        $asignacion = Asignacion::with('periodo')->findOrFail($id_asignacion);

        $this->verificarCatedratico($request, $id_asignacion);

        $bloqueado = $this->verificarPeriodoCerrado($asignacion);
        if ($bloqueado) {
            return $bloqueado;
        }

        DetalleCalificacion::where('id_evaluacion', $id_evaluacion)->delete();
        $evaluacion->delete();

        CalificacionService::recalcularNotasFinales(Asignacion::with(['zonas', 'evaluaciones'])->find($id_asignacion));

        return response()->json(null, 204);
    }

    private function verificarPeriodoCerrado(Asignacion $asignacion)
    {
        if (($asignacion->periodo?->estado ?? '') === 'cerrado') {
            return response()->json([
                'message' => 'No se pueden modificar calificaciones: el periodo académico está cerrado.',
            ], 422);
        }

        return null;
    }
}
