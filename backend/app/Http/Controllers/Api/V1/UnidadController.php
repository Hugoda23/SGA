<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Unidad;
use App\Models\Asignacion;
use App\Traits\VerificaPropietarioCurso;
use Illuminate\Http\Request;

class UnidadController extends Controller
{
    use VerificaPropietarioCurso;

    public function porAsignacion(Request $request, $id_asignacion)
    {
        $this->verificarCatedratico($request, $id_asignacion);

        return Unidad::where('id_asignacion', $id_asignacion)
            ->with('tareas.entregas')
            ->orderBy('numero_semana')
            ->get()
            ->map(function ($u) {
                return $this->serializar($u);
            });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_asignacion' => 'required|exists:asignacion,id_asignacion',
            'numero_semana' => 'nullable|integer|min:1',
            'titulo' => 'required|string|max:200',
            'temas' => 'nullable|string',
            'competencia' => 'nullable|string',
            'estado' => 'nullable|string|max:30',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $this->verificarCatedratico($request, $validated['id_asignacion']);

        $unidad = Unidad::create($validated);

        return response()->json($this->serializar($unidad), 201);
    }

    public function show(Request $request, $id_unidad)
    {
        $unidad = Unidad::with('tareas.entregas')->findOrFail($id_unidad);
        $this->verificarCatedratico($request, $unidad->id_asignacion);

        return response()->json($this->serializar($unidad));
    }

    public function update(Request $request, $id_unidad)
    {
        $unidad = Unidad::findOrFail($id_unidad);
        $this->verificarCatedratico($request, $unidad->id_asignacion);

        $validated = $request->validate([
            'numero_semana' => 'nullable|integer|min:1',
            'titulo' => 'sometimes|string|max:200',
            'temas' => 'nullable|string',
            'competencia' => 'nullable|string',
            'estado' => 'nullable|string|max:30',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $unidad->update($validated);

        return response()->json($this->serializar($unidad));
    }

    public function destroy(Request $request, $id_unidad)
    {
        $unidad = Unidad::findOrFail($id_unidad);
        $this->verificarCatedratico($request, $unidad->id_asignacion);

        $unidad->delete();

        return response()->json(null, 204);
    }

    private function serializar(Unidad $u)
    {
        return [
            'id_unidad' => $u->id_unidad,
            'id_asignacion' => $u->id_asignacion,
            'numero_semana' => $u->numero_semana,
            'titulo' => $u->titulo,
            'temas' => $u->temas,
            'competencia' => $u->competencia,
            'estado' => $u->estado,
            'fecha_inicio' => $u->fecha_inicio?->toDateString(),
            'fecha_fin' => $u->fecha_fin?->toDateString(),
            'tareas' => $u->tareas->map(fn ($t) => [
                'id_tarea' => $t->id_tarea,
                'titulo' => $t->titulo,
                'fecha_entrega' => $t->fecha_entrega,
                'total_entregas' => $t->entregas->count(),
            ])->values(),
        ];
    }

}
