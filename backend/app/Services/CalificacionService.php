<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\CalificacionFinal;
use App\Models\DetalleCalificacion;
use App\Models\EntregaTarea;

class CalificacionService
{
    /**
     * Recalcula la nota final de cada inscripción de la asignación.
     *
     * Regla de zonas: cada actividad (Evaluacion) vale sus `porcentaje`
     * puntos y cada Tarea vinculada a la zona vale sus `puntos`; la nota
     * obtenida en una zona es la suma de ambas (tope = puntos de la zona).
     * La nota final es la suma de las notas de zona escalada a 100. Si no
     * hay zonas, usa el promedio ponderado por porcentaje de Evaluacion
     * (comportamiento anterior) para no romper datos existentes — las
     * Tareas nunca participaron en ese modo legado.
     */
    public static function recalcularNotasFinales(Asignacion $asignacion): void
    {
        $asignacion->load(['zonas.evaluaciones', 'zonas.tareas', 'evaluaciones', 'inscripciones']);

        $zonas = $asignacion->zonas;
        $sinZona = $asignacion->evaluaciones->whereNull('id_zona')->values();

        // Si hay zonas definidas, la estructura por zonas es autoritativa:
        // las actividades sin zona no cuentan en la nota final.
        if ($zonas->isNotEmpty()) {
            $totalPuntos = $zonas->sum('puntos');
            if ($totalPuntos <= 0) {
                return;
            }

            $inscripciones = $asignacion->inscripciones;

            // Notas de las tareas vinculadas a alguna zona, por alumno — para
            // no disparar una query por alumno dentro del loop principal.
            $idsTareasEnZona = $zonas->flatMap->tareas->pluck('id_tarea');
            $idsAlumnos = $inscripciones->pluck('id_alumno');
            $entregasPorAlumno = $idsTareasEnZona->isNotEmpty() && $idsAlumnos->isNotEmpty()
                ? EntregaTarea::whereIn('id_tarea', $idsTareasEnZona)
                    ->whereIn('id_alumno', $idsAlumnos)
                    ->get()
                    ->groupBy('id_alumno')
                : collect();

            foreach ($inscripciones as $inscripcion) {
                $idInscripcion = $inscripcion->id_inscripcion;

                $detalles = DetalleCalificacion::whereIn(
                    'id_evaluacion',
                    $asignacion->evaluaciones->pluck('id_evaluacion')
                )->where('id_inscripcion', $idInscripcion)->get();

                $entregasAlumno = $entregasPorAlumno->get($inscripcion->id_alumno, collect())->keyBy('id_tarea');

                $notaFinal = 0;

                foreach ($zonas as $zona) {
                    $obtenidoEvaluaciones = $zona->evaluaciones->sum(function ($ev) use ($detalles) {
                        $detalle = $detalles->firstWhere('id_evaluacion', $ev->id_evaluacion);
                        return $detalle?->nota ?? 0;
                    });

                    $obtenidoTareas = $zona->tareas->sum(function ($t) use ($entregasAlumno) {
                        return $entregasAlumno->get($t->id_tarea)?->calificacion ?? 0;
                    });

                    $notaFinal += min($obtenidoEvaluaciones + $obtenidoTareas, (float) $zona->puntos);
                }

                $notaFinal = round($notaFinal * 100 / $totalPuntos, 2);

                CalificacionFinal::updateOrCreate(
                    ['id_inscripcion' => $idInscripcion],
                    ['nota_final' => $notaFinal]
                );
            }

            return;
        }

        // Sin zonas: promedio ponderado por porcentaje (comportamiento anterior)
        // para no romper datos existentes.
        $totalPuntos = $sinZona->sum('porcentaje');

        if ($totalPuntos <= 0) {
            return;
        }

        $inscripciones = $asignacion->inscripciones()->pluck('id_inscripcion');

        foreach ($inscripciones as $idInscripcion) {
            $detalles = DetalleCalificacion::whereIn(
                'id_evaluacion',
                $asignacion->evaluaciones->pluck('id_evaluacion')
            )->where('id_inscripcion', $idInscripcion)->get();

            $notaFinal = 0;

            foreach ($sinZona as $ev) {
                $detalle = $detalles->firstWhere('id_evaluacion', $ev->id_evaluacion);
                $notaFinal += $detalle?->nota ?? 0;
            }

            $notaFinal = round($notaFinal * 100 / $totalPuntos, 2);

            CalificacionFinal::updateOrCreate(
                ['id_inscripcion' => $idInscripcion],
                ['nota_final' => $notaFinal]
            );
        }
    }

    public static function recalcularParaInscripciones(array $idInscripciones): void
    {
        if (empty($idInscripciones)) {
            return;
        }

        $asignaciones = Asignacion::whereIn('id_asignacion', function ($q) use ($idInscripciones) {
            $q->select('id_asignacion')->from('inscripcion')->whereIn('id_inscripcion', $idInscripciones);
        })->get();

        foreach ($asignaciones as $asignacion) {
            self::recalcularNotasFinales($asignacion);
        }
    }
}
