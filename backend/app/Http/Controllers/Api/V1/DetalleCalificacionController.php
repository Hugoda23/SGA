<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DetalleCalificacion;
use Illuminate\Http\Request;

class DetalleCalificacionController extends Controller
{
    public function index()
    {
        return DetalleCalificacion::with('evaluacion', 'inscripcion')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_evaluacion' => 'required|exists:evaluacion,id_evaluacion',
            'id_inscripcion' => 'required|exists:inscripcion,id_inscripcion',
            'nota' => 'nullable|numeric|max:100',
        ]);

        $detalle = DetalleCalificacion::create($validated);

        return response()->json($detalle, 201);
    }

    public function show(DetalleCalificacion $detalleCalificacion)
    {
        return $detalleCalificacion->load('evaluacion', 'inscripcion');
    }

    public function update(Request $request, DetalleCalificacion $detalleCalificacion)
    {
        $validated = $request->validate([
            'nota' => 'nullable|numeric|max:100',
        ]);

        $detalleCalificacion->update($validated);

        return response()->json($detalleCalificacion);
    }

    public function destroy(DetalleCalificacion $detalleCalificacion)
    {
        $detalleCalificacion->delete();

        return response()->json(null, 204);
    }
}
