<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PeriodoAcademico;
use App\Services\CalificacionService;
use App\Services\PromocionService;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodoAcademicoController extends Controller
{
    use PreventsDeleteOnRelatedRecords;

    public function index()
    {
        return PeriodoAcademico::with('asignaciones')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'estado' => 'nullable|string|max:50',
        ]);

        $periodo = PeriodoAcademico::create($validated);

        return response()->json($periodo, 201);
    }

    public function show(PeriodoAcademico $periodoAcademico)
    {
        return $periodoAcademico->load('asignaciones');
    }

    public function update(Request $request, PeriodoAcademico $periodoAcademico)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'estado' => 'nullable|string|max:50',
        ]);

        $periodoAcademico->update($validated);

        return response()->json($periodoAcademico);
    }

    public function destroy(PeriodoAcademico $periodoAcademico)
    {
        return $this->deleteWithGuard(
            $periodoAcademico,
            fn ($p) => $p->asignaciones()->exists(),
            'No se puede eliminar el período académico porque tiene asignaciones asociadas.'
        );
    }

    /**
     * POST /v1/periodos-academicos/{periodo}/cerrar
     *
     * Cierra el periodo: recalcula las notas finales de todas sus asignaciones,
     * ejecuta la promoción de alumnos y bloquea nuevas ediciones de calificaciones.
     */
    public function cerrar(PeriodoAcademico $periodoAcademico)
    {
        if ($periodoAcademico->estado === 'cerrado') {
            return response()->json([
                'message' => 'El periodo académico ya está cerrado.',
            ], 422);
        }

        $resultado = DB::transaction(function () use ($periodoAcademico) {
            foreach ($periodoAcademico->asignaciones as $asignacion) {
                CalificacionService::recalcularNotasFinales($asignacion);
            }

            $periodoAcademico->update([
                'estado' => 'cerrado',
                'fecha_fin' => $periodoAcademico->fecha_fin ?? now()->toDateString(),
            ]);

            return app(PromocionService::class)->promoverPeriodo($periodoAcademico->id_periodo);
        });

        return response()->json([
            'message' => 'Periodo académico cerrado correctamente.',
            'resumen' => $resultado['resumen'],
        ]);
    }
}
