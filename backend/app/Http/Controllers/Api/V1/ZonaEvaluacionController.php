<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\ZonaEvaluacion;
use App\Services\CalificacionService;
use Illuminate\Http\Request;

class ZonaEvaluacionController extends Controller
{
    public function porAsignacion(Request $request, $id_asignacion)
    {
        $this->verificarCatedratico($request, $id_asignacion);

        return ZonaEvaluacion::where('id_asignacion', $id_asignacion)
            ->with('evaluaciones')
            ->orderBy('posicion')
            ->orderBy('id_zona')
            ->get()
            ->map(fn ($z) => $this->serializar($z))
            ->values();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_asignacion' => 'required|exists:asignacion,id_asignacion',
            'nombre' => 'required|string|max:100',
            'puntos' => 'required|numeric|gt:0',
            'posicion' => 'nullable|integer|min:0',
        ]);

        $this->verificarCatedratico($request, $validated['id_asignacion']);

        if (!$this->cabeEn100($validated['id_asignacion'], $validated['puntos'])) {
            return response()->json(['errors' => ['puntos' => ['El total de puntos de las zonas no puede exceder 100.']]], 422);
        }

        $zona = ZonaEvaluacion::create([
            'id_asignacion' => $validated['id_asignacion'],
            'nombre' => $validated['nombre'],
            'puntos' => $validated['puntos'],
            'posicion' => $validated['posicion'] ?? 0,
        ]);

        return response()->json($this->serializar($zona->load('evaluaciones')), 201);
    }

    public function update(Request $request, $id_zona)
    {
        $zona = ZonaEvaluacion::findOrFail($id_zona);
        $this->verificarCatedratico($request, $zona->id_asignacion);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'puntos' => 'sometimes|numeric|gt:0',
            'posicion' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['puntos'])) {
            if (!$this->cabeEn100($zona->id_asignacion, $validated['puntos'], $id_zona)) {
                return response()->json(['errors' => ['puntos' => ['El total de puntos de las zonas no puede exceder 100.']]], 422);
            }

            $consumido = $zona->puntosConsumidos();
            if ($validated['puntos'] < $consumido) {
                return response()->json([
                    'message' => "No puedes reducir \"{$zona->nombre}\" a {$validated['puntos']} pts: ya tiene {$consumido} pts asignados entre sus actividades y tareas.",
                    'errors' => ['puntos' => ["Ya hay {$consumido} pts asignados en esta zona (entre actividades y tareas). Reduce o elimina algunas primero."]],
                ], 422);
            }
        }

        $zona->update($validated);
        CalificacionService::recalcularNotasFinales(Asignacion::find($zona->id_asignacion));

        return response()->json($this->serializar($zona->fresh(['evaluaciones'])));
    }

    public function destroy(Request $request, $id_zona)
    {
        $zona = ZonaEvaluacion::findOrFail($id_zona);
        $this->verificarCatedratico($request, $zona->id_asignacion);

        if ($zona->evaluaciones()->count() > 0) {
            return response()->json(['message' => 'No se puede eliminar la zona porque tiene actividades asociadas. Mueve o elimina sus actividades primero.'], 422);
        }

        $zona->delete();
        CalificacionService::recalcularNotasFinales(Asignacion::find($zona->id_asignacion));

        return response()->json(null, 204);
    }

    private function serializar(ZonaEvaluacion $z)
    {
        return [
            'id_zona' => $z->id_zona,
            'id_asignacion' => $z->id_asignacion,
            'nombre' => $z->nombre,
            'puntos' => (float) $z->puntos,
            'posicion' => $z->posicion,
            'evaluaciones' => $z->evaluaciones->map(fn ($ev) => [
                'id_evaluacion' => $ev->id_evaluacion,
                'nombre' => $ev->nombre,
                'porcentaje' => (float) $ev->porcentaje,
                'unidad_academica' => $ev->unidad_academica,
            ])->values(),
        ];
    }

    private function cabeEn100($idAsignacion, $puntosNuevos, $idZona = null)
    {
        $total = ZonaEvaluacion::where('id_asignacion', $idAsignacion)
            ->when($idZona, fn ($q) => $q->where('id_zona', '!=', $idZona))
            ->sum('puntos');

        return ($total + $puntosNuevos) <= 100;
    }

    /**
     * Verifica que el catedrático autenticado sea dueño de la asignación.
     * Si el usuario no tiene perfil de catedrático (ej. admin) se permite el acceso.
     */
    private function verificarCatedratico(Request $request, $id_asignacion)
    {
        $usuario = $request->user();
        $catedratico = Catedratico::where('id_usuario', $usuario->id_usuario)->first();

        if ($catedratico) {
            $asignacion = Asignacion::find($id_asignacion);
            if (!$asignacion || $asignacion->id_catedratico !== $catedratico->id_catedratico) {
                return response()->json(['error' => 'No autorizado para este curso'], 403)->throwResponse();
            }
        }
    }
}
