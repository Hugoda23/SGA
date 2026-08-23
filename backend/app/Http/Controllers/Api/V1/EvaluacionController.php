<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Models\Asignacion;
use App\Models\ZonaEvaluacion;
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

        if (!empty($validated['id_zona'])) {
            $error = $this->validarCapacidadZona($validated['id_zona'], $validated['porcentaje'] ?? null);
            if ($error) {
                return $error;
            }
        }

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

        $idZona = array_key_exists('id_zona', $validated) ? $validated['id_zona'] : $evaluacion->id_zona;
        if (!empty($idZona)) {
            $porcentaje = array_key_exists('porcentaje', $validated) ? $validated['porcentaje'] : $evaluacion->porcentaje;
            $error = $this->validarCapacidadZona($idZona, $porcentaje, $evaluacion->id_evaluacion);
            if ($error) {
                return $error;
            }
        }

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

    /**
     * Verifica que la evaluación quepa en el presupuesto de puntos
     * disponible de su zona. Devuelve una respuesta 422 si no cabe.
     */
    private function validarCapacidadZona($idZona, $porcentaje, $idEvaluacionExcluir = null)
    {
        $zona = ZonaEvaluacion::find($idZona);

        if (!$zona) {
            return response()->json(['errors' => ['id_zona' => ['La zona indicada no existe.']]], 422);
        }

        $disponible = $zona->puntosDisponibles($idEvaluacionExcluir);

        if ((float) ($porcentaje ?? 0) > $disponible) {
            return response()->json([
                'message' => "La zona \"{$zona->nombre}\" solo tiene {$disponible} pts disponibles.",
                'errors' => ['porcentaje' => ["La zona \"{$zona->nombre}\" solo tiene {$disponible} pts disponibles."]],
            ], 422);
        }

        return null;
    }
}
