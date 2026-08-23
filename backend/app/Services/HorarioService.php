<?php

namespace App\Services;

use App\Models\HorarioDetalle;

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

        $inicioNuevo = strtotime($horaInicio);
        $finNuevo = strtotime($horaFin);

        $otrosHorarios = HorarioDetalle::where('dia_semana', $diaSemana)
            ->whereNotNull('hora_inicio')
            ->whereNotNull('hora_fin')
            ->where('id_asignacion', '!=', $idAsignacion)
            ->when($idHorarioExcluir, fn ($q) => $q->where('id_horario', '!=', $idHorarioExcluir))
            ->whereHas('asignacion', function ($q) use ($idAula, $idPeriodo) {
                $q->where('id_aula', $idAula)->where('id_periodo', $idPeriodo);
            })
            ->with('asignacion.curso')
            ->get();

        foreach ($otrosHorarios as $existente) {
            $inicioExistente = strtotime($existente->hora_inicio);
            $finExistente = strtotime($existente->hora_fin);

            if ($inicioNuevo < $finExistente && $inicioExistente < $finNuevo) {
                $curso = $existente->asignacion?->curso?->nombre_curso ?? 'otra asignación';

                return ["El aula ya está reservada de {$existente->hora_inicio} a {$existente->hora_fin} el {$diaSemana} por: {$curso}."];
            }
        }

        return [];
    }
}
