<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;

class AsignacionController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 50), 1000));
        $q = trim((string) $request->query('q', ''));

        $query = Asignacion::with('catedratico', 'curso', 'aula', 'periodo', 'grado', 'seccion', 'horarios', 'inscripciones');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->whereHas('curso', fn ($c) => $c->where('nombre_curso', 'ilike', "%{$q}%"))
                    ->orWhereHas('catedratico', fn ($c) => $c->where('nombre', 'ilike', "%{$q}%")->orWhere('apellido', 'ilike', "%{$q}%"))
                    ->orWhereHas('aula', fn ($c) => $c->where('nombre_aula', 'ilike', "%{$q}%"))
                    ->orWhereHas('periodo', fn ($c) => $c->where('nombre', 'ilike', "%{$q}%"));
            });
        }

        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_catedratico' => 'required|exists:catedratico,id_catedratico',
            'id_curso' => 'required|exists:curso,id_curso',
            'id_aula' => 'required|exists:aula,id_aula',
            'id_periodo' => 'required|exists:periodo_academico,id_periodo',
            'grado' => 'nullable|string|max:50',
            'seccion' => 'nullable|string|max:20',
        ]);

        $asignacion = Asignacion::create($validated);

        return response()->json($asignacion, 201);
    }

    public function show(Asignacion $asignacion)
    {
        return $asignacion->load('catedratico', 'curso', 'aula', 'periodo', 'horarios', 'tareas', 'inscripciones', 'evaluaciones');
    }

    public function update(Request $request, Asignacion $asignacion)
    {
        $validated = $request->validate([
            'id_catedratico' => 'sometimes|exists:catedratico,id_catedratico',
            'id_curso' => 'sometimes|exists:curso,id_curso',
            'id_aula' => 'sometimes|exists:aula,id_aula',
            'id_periodo' => 'sometimes|exists:periodo_academico,id_periodo',
            'grado' => 'nullable|string|max:50',
            'seccion' => 'nullable|string|max:20',
        ]);

        $asignacion->update($validated);

        return response()->json($asignacion);
    }

    public function destroy(Asignacion $asignacion)
    {
        return $this->deleteWithGuard(
            $asignacion,
            fn ($a) => $a->inscripciones()->exists() || $a->tareas()->exists() || $a->evaluaciones()->exists() || $a->horarios()->exists(),
            'No se puede eliminar la asignación porque tiene inscripciones, tareas, evaluaciones u horarios asociados.'
        );
    }
}
