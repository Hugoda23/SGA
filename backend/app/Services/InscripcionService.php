<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Asignacion;
use App\Models\Inscripcion;
use App\Models\Pensum;
use App\Models\PeriodoAcademico;

class InscripcionService
{
    /**
     * Inscribe al alumno en todos los cursos del pensum de una
     * carrera+grado — la misma combinación con la que se define un
     * pensum. La carrera es opcional (Básico no tiene carrera, eso
     * empieza en Diversificado); si no se manda, solo entra el pensum
     * definido sin carrera. De paso, deja esa carrera, grado y sección
     * como los actuales del alumno. Por cada curso crea (o reutiliza, si
     * ya existe) una Asignacion para ese curso/grado/sección/periodo —
     * sin catedrático ni aula todavía si es nueva ("pendiente de
     * asignación") — y lo inscribe ahí. La sección es opcional, pero al
     * indicarla separa a los alumnos de distintas secciones en
     * asignaciones (grupos de clase) distintas aunque compartan
     * curso/grado/periodo. Si el alumno ya está inscrito en el curso, lo
     * omite sin error.
     *
     * Un curso se omite (sin abortar el resto del pensum) si su horario ya
     * está definido y se cruza con el de otra clase donde el alumno ya está
     * inscrito ese mismo periodo — igual que un choque de horario al
     * inscribir en una asignación puntual, ver [[verificarReglas]].
     *
     * @return array{errores: string[], periodo?: PeriodoAcademico, inscripciones_creadas?: int, ya_inscrito?: int, choque_horario?: int, cursos?: string[], cursos_choque?: string[]}
     */
    public function inscribirPorGrado(Alumno $alumno, int $idGrado, ?int $idCarrera = null, ?int $idPeriodo = null, ?int $idSeccion = null): array
    {
        $periodo = $idPeriodo
            ? PeriodoAcademico::find($idPeriodo)
            : PeriodoAcademico::where('estado', 'activo')->first();

        if (!$periodo) {
            return ['errores' => ['No se encontró un periodo académico activo.']];
        }

        if ($periodo->estado === 'cerrado') {
            return ['errores' => ['No se puede inscribir: el periodo académico está cerrado.']];
        }

        // El pensum sin carrera (id_carrera null) aplica a cualquiera de
        // ese grado; si además se indicó una carrera, también entra el
        // pensum específico de esa carrera+grado.
        $cursosPensum = Pensum::where(function ($q) use ($idCarrera) {
                $q->whereNull('id_carrera');
                if ($idCarrera) {
                    $q->orWhere('id_carrera', $idCarrera);
                }
            })
            ->where(function ($q) use ($idGrado) {
                $q->where('id_grado', $idGrado)->orWhereNull('id_grado');
            })
            ->with('curso')
            ->get();

        if ($cursosPensum->isEmpty()) {
            return ['errores' => ['No hay pensum definido para esa carrera y grado.']];
        }

        $cambios = [];
        if ($alumno->id_grado_actual !== $idGrado) {
            $cambios['id_grado_actual'] = $idGrado;
        }
        if ($idCarrera && $alumno->id_carrera !== $idCarrera) {
            $cambios['id_carrera'] = $idCarrera;
        }
        if ($idSeccion && $alumno->id_seccion_actual !== $idSeccion) {
            $cambios['id_seccion_actual'] = $idSeccion;
        }
        if (!empty($cambios)) {
            $alumno->update($cambios);
        }

        $inscripcionesCreadas = 0;
        $yaInscrito = 0;
        $choqueHorario = 0;
        $cursosNombres = [];
        $cursosChoque = [];

        foreach ($cursosPensum as $pensum) {
            $asignacion = Asignacion::firstOrCreate(
                [
                    'id_curso' => $pensum->id_curso,
                    'id_grado' => $idGrado,
                    'id_seccion' => $idSeccion,
                    'id_periodo' => $periodo->id_periodo,
                ],
                [
                    'id_catedratico' => null,
                    'id_aula' => null,
                ]
            );

            $existeActiva = Inscripcion::where('id_alumno', $alumno->id_alumno)
                ->where('id_asignacion', $asignacion->id_asignacion)
                ->where('estado', 'activo')
                ->exists();

            if ($existeActiva) {
                $yaInscrito++;
                continue;
            }

            $nombreCurso = $pensum->curso?->nombre_curso ?? 'Curso';
            $cursoChoque = $this->cursoConChoqueHorario($alumno->id_alumno, $asignacion);

            if ($cursoChoque) {
                $choqueHorario++;
                $cursosChoque[] = "{$nombreCurso} (choca con {$cursoChoque})";
                continue;
            }

            Inscripcion::create([
                'id_alumno' => $alumno->id_alumno,
                'id_asignacion' => $asignacion->id_asignacion,
                'estado' => 'activo',
            ]);

            $inscripcionesCreadas++;
            $cursosNombres[] = $nombreCurso;
        }

        return [
            'errores' => [],
            'periodo' => $periodo,
            'inscripciones_creadas' => $inscripcionesCreadas,
            'ya_inscrito' => $yaInscrito,
            'choque_horario' => $choqueHorario,
            'cursos' => $cursosNombres,
            'cursos_choque' => $cursosChoque,
        ];
    }

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

        $cursoChoque = $this->cursoConChoqueHorario($idAlumno, $asignacion);
        if ($cursoChoque) {
            $errores[] = "La asignación se cruza con el horario de otra clase del alumno: {$cursoChoque}.";
        }

        return $errores;
    }

    /**
     * Nombre del curso de otra inscripción activa del alumno (mismo
     * periodo) cuyo horario se cruza con el de $asignacion, o null si no
     * hay ningún choque. Se usa tanto al inscribir en una asignación
     * puntual como al inscribir por grado (pensum completo).
     */
    private function cursoConChoqueHorario(int $idAlumno, Asignacion $asignacion): ?string
    {
        $nuevosHorarios = $asignacion->horarios->filter(fn ($h) => $h->dia_semana && $h->hora_inicio && $h->hora_fin);
        if ($nuevosHorarios->isEmpty()) {
            return null;
        }

        $otras = Inscripcion::where('id_alumno', $idAlumno)
            ->where('id_asignacion', '!=', $asignacion->id_asignacion)
            ->where('estado', 'activo')
            ->whereHas('asignacion', fn ($q) => $q->where('id_periodo', $asignacion->id_periodo))
            ->with('asignacion.horarios', 'asignacion.curso')
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
                        return $inscripcion->asignacion->curso?->nombre_curso ?? 'otra clase';
                    }
                }
            }
        }

        return null;
    }
}
