<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DetalleCalificacion;
use App\Models\Inscripcion;
use App\Traits\VerificaPropietarioCurso;
use Illuminate\Http\Request;

class DetalleCalificacionController extends Controller
{
    use VerificaPropietarioCurso;

    public function index(Request $request)
    {
        $query = DetalleCalificacion::with('evaluacion', 'inscripcion');

        $catedratico = $this->catedraticoActual($request);
        if ($catedratico) {
            $query->whereHas('inscripcion.asignacion', fn ($a) => $a->where('id_catedratico', $catedratico->id_catedratico));
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_evaluacion' => 'required|exists:evaluacion,id_evaluacion',
            'id_inscripcion' => 'required|exists:inscripcion,id_inscripcion',
            'nota' => 'nullable|numeric|max:100',
        ]);

        $idAsignacion = Inscripcion::find($validated['id_inscripcion'])->id_asignacion;
        $this->verificarCatedratico($request, $idAsignacion);

        $detalle = DetalleCalificacion::create($validated);

        return response()->json($detalle, 201);
    }

    public function show(Request $request, DetalleCalificacion $detalleCalificacion)
    {
        $this->verificarCatedratico($request, $detalleCalificacion->inscripcion->id_asignacion);

        return $detalleCalificacion->load('evaluacion', 'inscripcion');
    }

    public function update(Request $request, DetalleCalificacion $detalleCalificacion)
    {
        $this->verificarCatedratico($request, $detalleCalificacion->inscripcion->id_asignacion);

        $validated = $request->validate([
            'nota' => 'nullable|numeric|max:100',
        ]);

        $detalleCalificacion->update($validated);

        return response()->json($detalleCalificacion);
    }

    public function destroy(Request $request, DetalleCalificacion $detalleCalificacion)
    {
        $this->verificarCatedratico($request, $detalleCalificacion->inscripcion->id_asignacion);

        $detalleCalificacion->delete();

        return response()->json(null, 204);
    }
}
