<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReporteGenerado;
use Illuminate\Http\Request;

class ReporteGeneradoController extends Controller
{
    public function index()
    {
        return ReporteGenerado::with('usuario')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => 'nullable|exists:usuario,id_usuario',
            'tipo_reporte' => 'nullable|string|max:100',
            'tiempo_generacion' => 'nullable|numeric',
        ]);

        $reporte = ReporteGenerado::create($validated);

        return response()->json($reporte, 201);
    }

    public function show(ReporteGenerado $reporteGenerado)
    {
        return $reporteGenerado->load('usuario');
    }

    public function destroy(ReporteGenerado $reporteGenerado)
    {
        $reporteGenerado->delete();

        return response()->json(null, 204);
    }
}
