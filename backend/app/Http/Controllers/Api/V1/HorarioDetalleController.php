<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\HorarioDetalle;
use App\Services\HorarioService;
use Illuminate\Http\Request;

class HorarioDetalleController extends Controller
{
    public function index()
    {
        return HorarioDetalle::with('asignacion')->get();
    }

    public function store(Request $request, HorarioService $horarioService)
    {
        $validated = $request->validate([
            'id_asignacion' => 'required|exists:asignacion,id_asignacion',
            'dia_semana' => 'nullable|string|max:20',
            'hora_inicio' => 'nullable|date_format:H:i:s',
            'hora_fin' => 'nullable|date_format:H:i:s',
        ]);

        $asignacion = Asignacion::findOrFail($validated['id_asignacion']);
        $horaInicio = $validated['hora_inicio'] ?? null;
        $horaFin = $validated['hora_fin'] ?? null;

        $errorRango = $this->validarRangoHoras($horaInicio, $horaFin);
        if ($errorRango) {
            return response()->json(['message' => 'No se pudo guardar el horario.', 'errores' => [$errorRango]], 422);
        }

        $diaSemana = $validated['dia_semana'] ?? null;

        $errores = $horarioService->verificarChoqueAula(
            $validated['id_asignacion'],
            $asignacion->id_aula,
            $asignacion->id_periodo,
            $diaSemana,
            $horaInicio,
            $horaFin
        );

        if (empty($errores)) {
            $errores = $horarioService->verificarChoqueCatedratico(
                $validated['id_asignacion'],
                $asignacion->id_catedratico,
                $asignacion->id_periodo,
                $diaSemana,
                $horaInicio,
                $horaFin
            );
        }

        if (empty($errores)) {
            $errores = $horarioService->verificarChoqueAlumnos(
                $validated['id_asignacion'],
                $asignacion->id_periodo,
                $diaSemana,
                $horaInicio,
                $horaFin
            );
        }

        if (!empty($errores)) {
            return response()->json(['message' => 'No se pudo guardar el horario.', 'errores' => $errores], 422);
        }

        $horario = HorarioDetalle::create($validated);

        return response()->json($horario, 201);
    }

    public function show(HorarioDetalle $horarioDetalle)
    {
        return $horarioDetalle->load('asignacion');
    }

    public function update(Request $request, HorarioDetalle $horarioDetalle, HorarioService $horarioService)
    {
        $validated = $request->validate([
            'dia_semana' => 'nullable|string|max:20',
            'hora_inicio' => 'nullable|date_format:H:i:s',
            'hora_fin' => 'nullable|date_format:H:i:s',
        ]);

        $asignacion = $horarioDetalle->asignacion;
        $horaInicio = $validated['hora_inicio'] ?? $horarioDetalle->hora_inicio;
        $horaFin = $validated['hora_fin'] ?? $horarioDetalle->hora_fin;

        $errorRango = $this->validarRangoHoras($horaInicio, $horaFin);
        if ($errorRango) {
            return response()->json(['message' => 'No se pudo actualizar el horario.', 'errores' => [$errorRango]], 422);
        }

        $diaSemana = $validated['dia_semana'] ?? $horarioDetalle->dia_semana;

        $errores = $horarioService->verificarChoqueAula(
            $horarioDetalle->id_asignacion,
            $asignacion->id_aula,
            $asignacion->id_periodo,
            $diaSemana,
            $horaInicio,
            $horaFin,
            $horarioDetalle->id_horario
        );

        if (empty($errores)) {
            $errores = $horarioService->verificarChoqueCatedratico(
                $horarioDetalle->id_asignacion,
                $asignacion->id_catedratico,
                $asignacion->id_periodo,
                $diaSemana,
                $horaInicio,
                $horaFin,
                $horarioDetalle->id_horario
            );
        }

        if (empty($errores)) {
            $errores = $horarioService->verificarChoqueAlumnos(
                $horarioDetalle->id_asignacion,
                $asignacion->id_periodo,
                $diaSemana,
                $horaInicio,
                $horaFin,
                $horarioDetalle->id_horario
            );
        }

        if (!empty($errores)) {
            return response()->json(['message' => 'No se pudo actualizar el horario.', 'errores' => $errores], 422);
        }

        $horarioDetalle->update($validated);

        return response()->json($horarioDetalle);
    }

    public function destroy(HorarioDetalle $horarioDetalle)
    {
        $horarioDetalle->delete();

        return response()->json(null, 204);
    }

    private function validarRangoHoras(?string $horaInicio, ?string $horaFin): ?string
    {
        if ($horaInicio !== null && $horaFin !== null && $horaFin <= $horaInicio) {
            return 'La hora de fin debe ser posterior a la hora de inicio.';
        }

        return null;
    }
}
