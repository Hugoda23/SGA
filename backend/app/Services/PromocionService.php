<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Configuracion;
use App\Models\Grado;
use App\Models\Inscripcion;

class PromocionService
{
    /**
     * Evalúa la promoción de los alumnos inscritos en un periodo académico.
     *
     * Un alumno aprueba el periodo si todas sus inscripciones del periodo tienen
     * nota final mayor o igual a la nota mínima configurada. Los que aprueban
     * pasan al grado siguiente (ordenado por id_grado); si ya están en el último
     * grado, su estado académico pasa a 'egresado'. Los que no aprueban se
     * consideran repetidores y conservan su grado actual.
     *
     * @return array{resumen: array, detalle: array}
     */
    public function promoverPeriodo(int $idPeriodo, bool $aplicar = true): array
    {
        $notaMinima = (int) Configuracion::get('nota_minima', 61);
        $gradosOrden = Grado::orderBy('id_grado')->pluck('id_grado')->values();

        $inscripciones = Inscripcion::whereHas('asignacion', function ($q) use ($idPeriodo) {
            $q->where('id_periodo', $idPeriodo);
        })->with([
            'asignacion:id_asignacion,id_grado',
            'calificacionesFinales:id_inscripcion,nota_final',
        ])->get();

        $resumen = [
            'total' => 0,
            'aprobados' => 0,
            'reprobados' => 0,
            'sin_notas' => 0,
            'egresados' => 0,
        ];
        $detalle = [];

        foreach ($inscripciones->groupBy('id_alumno') as $idAlumno => $inscripcionesAlumno) {
            $resumen['total']++;

            $sinNotas = $inscripcionesAlumno->contains(function ($i) {
                $final = $i->calificacionesFinales->first();
                return $final === null || $final->nota_final === null;
            });

            if ($sinNotas) {
                $resumen['sin_notas']++;
                $detalle[$idAlumno] = 'sin_notas';
                continue;
            }

            $reprobado = $inscripcionesAlumno->contains(function ($i) use ($notaMinima) {
                return (float) $i->calificacionesFinales->first()->nota_final < $notaMinima;
            });

            if ($reprobado) {
                $resumen['reprobados']++;
                $detalle[$idAlumno] = 'reprobado';
                continue;
            }

            $resumen['aprobados']++;

            if (! $aplicar) {
                $detalle[$idAlumno] = 'promovido';
                continue;
            }

            $alumno = Alumno::find($idAlumno);
            $gradoActual = $alumno?->id_grado_actual ?? $inscripcionesAlumno->first()->asignacion->id_grado;
            $indice = $gradosOrden->search($gradoActual);
            $siguiente = $indice !== false ? $gradosOrden->get($indice + 1) : null;

            if ($gradoActual === null || $siguiente === null) {
                $alumno?->update(['estado_academico' => 'egresado']);
                $resumen['egresados']++;
                $detalle[$idAlumno] = 'egresado';
            } else {
                $alumno?->update(['id_grado_actual' => $siguiente]);
                $detalle[$idAlumno] = 'promovido';
            }
        }

        return ['resumen' => $resumen, 'detalle' => $detalle];
    }
}
