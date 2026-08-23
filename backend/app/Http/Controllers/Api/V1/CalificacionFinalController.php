<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CalificacionFinal;
use App\Models\Inscripcion;
use App\Traits\VerificaPropietarioCurso;
use Illuminate\Http\Request;

class CalificacionFinalController extends Controller
{
    use VerificaPropietarioCurso;

    public function index(Request $request)
    {
        $query = CalificacionFinal::with('inscripcion');

        $catedratico = $this->catedraticoActual($request);
        if ($catedratico) {
            $query->whereHas('inscripcion.asignacion', fn ($a) => $a->where('id_catedratico', $catedratico->id_catedratico));
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_inscripcion' => 'required|exists:inscripcion,id_inscripcion',
            'unidad_academica' => 'nullable|integer',
            'nota_final' => 'nullable|numeric|max:100',
            'observaciones' => 'nullable|string',
        ]);

        $idAsignacion = Inscripcion::find($validated['id_inscripcion'])->id_asignacion;
        $this->verificarCatedratico($request, $idAsignacion);

        if ($this->periodoCerrado($validated['id_inscripcion'])) {
            return $this->respuestaPeriodoCerrado();
        }

        $calificacion = CalificacionFinal::create($validated);

        return response()->json($calificacion, 201);
    }

    public function show(Request $request, CalificacionFinal $calificacionFinal)
    {
        $this->verificarCatedratico($request, $calificacionFinal->inscripcion->id_asignacion);

        return $calificacionFinal->load('inscripcion');
    }

    public function update(Request $request, CalificacionFinal $calificacionFinal)
    {
        $this->verificarCatedratico($request, $calificacionFinal->inscripcion->id_asignacion);

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

    public function destroy(Request $request, CalificacionFinal $calificacionFinal)
    {
        $this->verificarCatedratico($request, $calificacionFinal->inscripcion->id_asignacion);

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
