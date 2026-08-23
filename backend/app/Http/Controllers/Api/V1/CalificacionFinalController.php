<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CalificacionFinal;
use App\Models\Inscripcion;
use Illuminate\Http\Request;

class CalificacionFinalController extends Controller
{
    public function index()
    {
        return CalificacionFinal::with('inscripcion')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_inscripcion' => 'required|exists:inscripcion,id_inscripcion',
            'unidad_academica' => 'nullable|integer',
            'nota_final' => 'nullable|numeric|max:100',
            'observaciones' => 'nullable|string',
        ]);

        if ($this->periodoCerrado($validated['id_inscripcion'])) {
            return $this->respuestaPeriodoCerrado();
        }

        $calificacion = CalificacionFinal::create($validated);

        return response()->json($calificacion, 201);
    }

    public function show(CalificacionFinal $calificacionFinal)
    {
        return $calificacionFinal->load('inscripcion');
    }

    public function update(Request $request, CalificacionFinal $calificacionFinal)
    {
        $validated = $request->validate([
            'unidad_academica' => 'nullable|integer',
            'nota_final' => 'nullable|numeric|max:100',
            'observaciones' => 'nullable|string',
        ]);

        if ($this->periodoCerrado($calificacionFinal->id_inscripcion)) {
            return $this->respuestaPeriodoCerrado();
        }

        $calificacionFinal->update($validated);

        return response()->json($calificacionFinal);
    }

    public function destroy(CalificacionFinal $calificacionFinal)
    {
        if ($this->periodoCerrado($calificacionFinal->id_inscripcion)) {
            return $this->respuestaPeriodoCerrado();
        }

        $calificacionFinal->delete();

        return response()->json(null, 204);
    }

    private function periodoCerrado(int $idInscripcion): bool
    {
        return Inscripcion::where('id_inscripcion', $idInscripcion)
            ->whereHas('asignacion.periodo', fn ($q) => $q->where('estado', 'cerrado'))
            ->exists();
    }

    private function respuestaPeriodoCerrado()
    {
        return response()->json([
            'message' => 'No se pueden modificar calificaciones: el periodo académico está cerrado.',
        ], 422);
    }
}
