<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HorarioDetalle;
use Illuminate\Http\Request;

class HorarioDetalleController extends Controller
{
    public function index()
    {
        return HorarioDetalle::with('asignacion')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_asignacion' => 'required|exists:asignacion,id_asignacion',
            'dia_semana' => 'nullable|string|max:20',
            'hora_inicio' => 'nullable|date_format:H:i:s',
            'hora_fin' => 'nullable|date_format:H:i:s',
        ]);

        $horario = HorarioDetalle::create($validated);

        return response()->json($horario, 201);
    }

    public function show(HorarioDetalle $horarioDetalle)
    {
        return $horarioDetalle->load('asignacion');
    }

    public function update(Request $request, HorarioDetalle $horarioDetalle)
    {
        $validated = $request->validate([
            'dia_semana' => 'nullable|string|max:20',
            'hora_inicio' => 'nullable|date_format:H:i:s',
            'hora_fin' => 'nullable|date_format:H:i:s',
        ]);

        $horarioDetalle->update($validated);

        return response()->json($horarioDetalle);
    }

    public function destroy(HorarioDetalle $horarioDetalle)
    {
        $horarioDetalle->delete();

        return response()->json(null, 204);
    }
}
