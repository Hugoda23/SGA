<?php

namespace App\Services;

use App\Models\HorarioDetalle;
use App\Models\Inscripcion;

class HorarioService
{
    /**
     * Verifica que un horario (día + rango de horas) en un aula/periodo dados
     * no choque con el horario de otra asignación que use la misma aula en el
     * mismo periodo académico. Evita que un aula quede doble-reservada.
     *
     * Recibe id_aula/id_periodo explícitos (en vez de leerlos de la asignación
     * en BD) para poder validar también un cambio de aula/periodo todavía no
     * guardado (ver AsignacionController::update).
     *
     * @param  int|null  $idHorarioExcluir  Id del propio horario, para no compararlo consigo mismo al actualizar.
     */
    public function verificarChoqueAula(
        int $idAsignacion,
        ?int $idAula,
        ?int $idPeriodo,
        ?string $diaSemana,
        ?string $horaInicio,
        ?string $horaFin,
        ?int $idHorarioExcluir = null
    ): array {
        if (!$diaSemana || !$horaInicio || !$horaFin || !$idAula) {
            return [];
        }

        $choque = $this->buscarPrimerChoque(
            $idAsignacion,
            ['id_aula' => $idAula, 'id_periodo' => $idPeriodo],
            $diaSemana,
            $horaInicio,
            $horaFin,
            $idHorarioExcluir
        );

        if (!$choque) {
            return [];
        }

        $curso = $choque->asignacion?->curso?->nombre_curso ?? 'otra asignación';

        return ["El aula ya está reservada de {$choque->hora_inicio} a {$choque->hora_fin} el {$diaSemana} por: {$curso}."];
    }

    /**
     * Verifica que el catedrático no quede con dos clases al mismo tiempo:
     * un catedrático no puede estar en dos aulas a la vez, sin importar el
     * aula, mientras el horario caiga dentro del mismo periodo académico.
     */
    public function verificarChoqueCatedratico(
        int $idAsignacion,
        ?int $idCatedratico,
        ?int $idPeriodo,
        ?string $diaSemana,
        ?string $horaInicio,
        ?string $horaFin,
        ?int $idHorarioExcluir = null
    ): array {
        if (!$diaSemana || !$horaInicio || !$horaFin || !$idCatedratico) {
            return [];
        }

        $choque = $this->buscarPrimerChoque(
            $idAsignacion,
            ['id_catedratico' => $idCatedratico, 'id_periodo' => $idPeriodo],
            $diaSemana,
            $horaInicio,
            $horaFin,
            $idHorarioExcluir
        );

        if (!$choque) {
            return [];
        }

        $curso = $choque->asignacion?->curso?->nombre_curso ?? 'otra asignación';

        return ["El catedrático ya tiene clase de {$choque->hora_inicio} a {$choque->hora_fin} el {$diaSemana} en: {$curso}."];
    }

    /**
     * Verifica que ningún alumno ya inscrito en esta asignación quede con dos
     * clases al mismo tiempo: revisa, para cada alumno activo aquí, si alguna
     * de sus otras inscripciones activas del mismo periodo tiene un horario
     * que se cruce con el nuevo.
     */
    public function verificarChoqueAlumnos(
        int $idAsignacion,
        ?int $idPeriodo,
        ?string $diaSemana,
        ?string $horaInicio,
        ?string $horaFin,
        ?int $idHorarioExcluir = null
    ): array {
        if (!$diaSemana || !$horaInicio || !$horaFin) {
            return [];
        }

        $inicioNuevo = strtotime($horaInicio);
        $finNuevo = strtotime($horaFin);

        $inscritos = Inscripcion::where('id_asignacion', $idAsignacion)
            ->where('estado', 'activo')
            ->with('alumno')
            ->get();

        foreach ($inscritos as $inscripcion) {
            $otras = Inscripcion::where('id_alumno', $inscripcion->id_alumno)
                ->where('id_asignacion', '!=', $idAsignacion)
                ->where('estado', 'activo')
                ->whereHas('asignacion', fn ($q) => $q->where('id_periodo', $idPeriodo))
                ->with('asignacion.curso', 'asignacion.horarios')
                ->get();

            foreach ($otras as $otra) {
                foreach ($otra->asignacion->horarios as $existente) {
                    if ($existente->dia_semana !== $diaSemana || !$existente->hora_inicio || !$existente->hora_fin) {
                        continue;
                    }
                    if ($idHorarioExcluir && $existente->id_horario === $idHorarioExcluir) {
                        continue;
                    }

                    $inicioExistente = strtotime($existente->hora_inicio);
                    $finExistente = strtotime($existente->hora_fin);

                    if ($inicioNuevo < $finExistente && $inicioExistente < $finNuevo) {
                        $alumno = trim("{$inscripcion->alumno?->nombre} {$inscripcion->alumno?->apellido}") ?: 'Un alumno inscrito aquí';
                        $curso = $otra->asignacion->curso?->nombre_curso ?? 'otra clase';

                        return ["{$alumno} ya tiene clase de {$existente->hora_inicio} a {$existente->hora_fin} el {$diaSemana} en: {$curso}."];
                    }
                }
            }
        }

        return [];
    }

    /**
     * Primer HorarioDetalle de otra asignación (que cumpla los filtros dados,
     * ej. misma aula o mismo catedrático, siempre + mismo periodo) cuyo
     * horario se cruce con el rango nuevo, el mismo día.
     */
    private function buscarPrimerChoque(
        int $idAsignacion,
        array $filtrosAsignacion,
        string $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?int $idHorarioExcluir
    ): ?HorarioDetalle {
        $inicioNuevo = strtotime($horaInicio);
        $finNuevo = strtotime($horaFin);

        return HorarioDetalle::where('dia_semana', $diaSemana)
            ->whereNotNull('hora_inicio')
            ->whereNotNull('hora_fin')
            ->where('id_asignacion', '!=', $idAsignacion)
            ->when($idHorarioExcluir, fn ($q) => $q->where('id_horario', '!=', $idHorarioExcluir))
            ->whereHas('asignacion', fn ($q) => $q->where($filtrosAsignacion))
            ->with('asignacion.curso')
            ->get()
            ->first(function ($existente) use ($inicioNuevo, $finNuevo) {
                $inicioExistente = strtotime($existente->hora_inicio);
                $finExistente = strtotime($existente->hora_fin);

                return $inicioNuevo < $finExistente && $inicioExistente < $finNuevo;
            });
    }
}
