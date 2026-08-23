<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;

class AulaController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index()
    {
        return Aula::with('edificio', 'asignaciones')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_aula' => 'required|string|max:100',
            'capacidad' => 'nullable|integer',
            'id_edificio' => 'nullable|exists:edificio,id_edificio',
        ]);

        $aula = Aula::create($validated);

        return response()->json($aula, 201);
    }

    public function show(Aula $aula)
    {
        return $aula->load('edificio', 'asignaciones');
    }

    public function update(Request $request, Aula $aula)
    {
        $validated = $request->validate([
            'nombre_aula' => 'sometimes|string|max:100',
            'capacidad' => 'nullable|integer',
            'id_edificio' => 'nullable|exists:edificio,id_edificio',
        ]);

        $aula->update($validated);

        return response()->json($aula);
    }

    public function destroy(Aula $aula)
    {
        return $this->deleteWithGuard(
            $aula,
            fn ($a) => $a->asignaciones()->exists(),
            'No se puede eliminar el aula porque tiene asignaciones asociadas.'
        );
    }
}
