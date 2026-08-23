<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Inscripcion;

class InscripcionService
{
    /**
     * Valida las reglas de negocio de una inscripción.
     *
     * @return string[] Lista de errores (vacía si la inscripción es válida).
     */
    public function verificarReglas(int $idAlumno, int $idAsignacion): array
    {
        $asignacion = Asignacion::with(['periodo', 'aula', 'curso.carreras', 'horarios'])
            ->findOrFail($idAsignacion);

        $errores = [];

        if (($asignacion->periodo?->estado ?? '') === 'cerrado') {
            $errores[] = 'No se puede inscribir: el periodo académico está cerrado.';
        }

        $yaInscrito = Inscripcion::where('id_alumno', $idAlumno)
            ->where('id_asignacion', $idAsignacion)
            ->where('estado', 'activo')
            ->exists();

        if ($yaInscrito) {
            $errores[] = 'El alumno ya está inscrito en esta asignación.';
        }

        $capacidad = (int) ($asignacion->aula?->capacidad ?? 0);
        if ($capacidad > 0) {
            $ocupados = Inscripcion::where('id_asignacion', $idAsignacion)
                ->where('estado', 'activo')
                ->count();

            if ($ocupados >= $capacidad) {
                $errores[] = 'El aula ha alcanzado su cupo máximo de ' . $capacidad . ' alumnos.';
            }
        }

        $carrerasCurso = $asignacion->curso?->carreras ?? collect();
        $alumnoCarrera = \App\Models\Alumno::find($idAlumno)?->id_carrera;

        if ($carrerasCurso->isNotEmpty() && $alumnoCarrera && !$carrerasCurso->contains('id_carrera', $alumnoCarrera)) {
            $errores[] = 'La carrera del alumno no corresponde con el curso de la asignación.';
        }

        if ($this->hayChoqueHorario($idAlumno, $asignacion)) {
            $errores[] = 'La asignación se cruza con el horario de otra asignación del alumno.';
        }

        return $errores;
    }

    private function hayChoqueHorario(int $idAlumno, Asignacion $asignacion): bool
    {
        $nuevosHorarios = $asignacion->horarios->filter(fn ($h) => $h->dia_semana && $h->hora_inicio && $h->hora_fin);
        if ($nuevosHorarios->isEmpty()) {
            return false;
        }

        $otras = Inscripcion::where('id_alumno', $idAlumno)
            ->where('estado', 'activo')
            ->whereHas('asignacion', fn ($q) => $q->where('id_periodo', $asignacion->id_periodo))
            ->with('asignacion.horarios')
            ->get();

        foreach ($nuevosHorarios as $nuevo) {
            $inicioNuevo = strtotime($nuevo->hora_inicio);
            $finNuevo = strtotime($nuevo->hora_fin);

            foreach ($otras as $inscripcion) {
                foreach ($inscripcion->asignacion->horarios as $existente) {
                    if ($existente->dia_semana !== $nuevo->dia_semana) {
                        continue;
                    }
                    if (!$existente->hora_inicio || !$existente->hora_fin) {
                        continue;
                    }

                    $inicioExistente = strtotime($existente->hora_inicio);
                    $finExistente = strtotime($existente->hora_fin);

                    if ($inicioNuevo < $finExistente && $inicioExistente < $finNuevo) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
