<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Models\Asignacion;
use App\Services\CalificacionService;
use App\Services\NotificacionService;
use Illuminate\Http\Request;

class EvaluacionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 50), 1000));
        $q = trim((string) $request->query('q', ''));

        $query = Evaluacion::with('asignacion', 'detalleCalificaciones');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'ilike', "%{$q}%")
                    ->orWhereHas('asignacion.curso', fn ($c) => $c->where('nombre_curso', 'ilike', "%{$q}%"));
            });
        }

        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_asignacion' => 'required|exists:asignacion,id_asignacion',
            'id_zona' => 'nullable|exists:zona_evaluacion,id_zona',
            'unidad_academica' => 'nullable|integer',
            'nombre' => 'nullable|string|max:100',
            'porcentaje' => 'nullable|numeric|max:100',
        ]);

        $evaluacion = Evaluacion::create($validated);

        CalificacionService::recalcularNotasFinales(Asignacion::find($evaluacion->id_asignacion));

        $asignacion = Asignacion::with('curso')->find($evaluacion->id_asignacion);
        $curso = $asignacion?->curso?->nombre_curso ?? 'el curso';
        NotificacionService::paraAlumnosDeAsignacion(
            $asignacion,
            "Nueva evaluación \"{$evaluacion->nombre}\" publicada en {$curso}."
        );

        return response()->json($evaluacion, 201);
    }

    public function show(Evaluacion $evaluacion)
    {
        return $evaluacion->load('asignacion', 'detalleCalificaciones');
    }

    public function update(Request $request, Evaluacion $evaluacion)
    {
        $validated = $request->validate([
            'id_zona' => 'nullable|exists:zona_evaluacion,id_zona',
            'unidad_academica' => 'nullable|integer',
            'nombre' => 'nullable|string|max:100',
            'porcentaje' => 'nullable|numeric|max:100',
        ]);

        $evaluacion->update($validated);

        CalificacionService::recalcularNotasFinales(Asignacion::find($evaluacion->id_asignacion));

        return response()->json($evaluacion);
    }

    public function destroy(Evaluacion $evaluacion)
    {
        $id_asignacion = $evaluacion->id_asignacion;
        $evaluacion->delete();

        CalificacionService::recalcularNotasFinales(Asignacion::find($id_asignacion));

        return response()->json(null, 204);
    }
}
