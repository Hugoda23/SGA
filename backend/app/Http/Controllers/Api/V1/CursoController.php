<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index()
    {
        return Curso::with(['carreras', 'asignaciones.catedratico', 'asignaciones.inscripciones'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_curso' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'creditos' => 'nullable|integer|min:0|max:20',
            'carreras' => 'nullable|array',
            'carreras.*' => 'exists:carrera,id_carrera',
        ]);

        $curso = Curso::create([
            'nombre_curso' => $validated['nombre_curso'],
            'descripcion' => $validated['descripcion'] ?? null,
            'creditos' => $validated['creditos'] ?? null,
        ]);

        $curso->carreras()->sync($validated['carreras'] ?? []);

        return response()->json($curso->load('carreras'), 201);
    }

    public function show(Curso $curso)
    {
        return $curso->load('carreras', 'asignaciones');
    }

    public function update(Request $request, Curso $curso)
    {
        $validated = $request->validate([
            'nombre_curso' => 'sometimes|string|max:150',
            'descripcion' => 'nullable|string',
            'creditos' => 'nullable|integer|min:0|max:20',
            'carreras' => 'nullable|array',
            'carreras.*' => 'exists:carrera,id_carrera',
        ]);

        $curso->update([
            'nombre_curso' => $validated['nombre_curso'] ?? $curso->nombre_curso,
            'descripcion' => array_key_exists('descripcion', $validated) ? $validated['descripcion'] : $curso->descripcion,
            'creditos' => array_key_exists('creditos', $validated) ? $validated['creditos'] : $curso->creditos,
        ]);

        if (array_key_exists('carreras', $validated)) {
            $curso->carreras()->sync($validated['carreras'] ?? []);
        }

        return response()->json($curso->load('carreras'));
    }

    public function destroy(Curso $curso)
    {
        return $this->deleteWithGuard(
            $curso,
            fn ($c) => $c->asignaciones()->exists() || $c->pensums()->exists(),
            'No se puede eliminar el curso porque tiene registros asociados (docentes asignados o pensum).'
        );
    }
}
