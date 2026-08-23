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

        $errores = $horarioService->verificarChoqueAula(
            $validated['id_asignacion'],
            $asignacion->id_aula,
            $asignacion->id_periodo,
            $validated['dia_semana'] ?? null,
            $validated['hora_inicio'] ?? null,
            $validated['hora_fin'] ?? null
        );

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

        $errores = $horarioService->verificarChoqueAula(
            $horarioDetalle->id_asignacion,
            $asignacion->id_aula,
            $asignacion->id_periodo,
            $validated['dia_semana'] ?? $horarioDetalle->dia_semana,
            $validated['hora_inicio'] ?? $horarioDetalle->hora_inicio,
            $validated['hora_fin'] ?? $horarioDetalle->hora_fin,
            $horarioDetalle->id_horario
        );

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
}
