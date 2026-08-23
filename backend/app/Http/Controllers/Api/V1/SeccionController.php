<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Seccion;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;

class SeccionController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index()
    {
        return Seccion::orderBy('nombre')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:20|unique:seccion,nombre',
        ]);

        $seccion = Seccion::create($validated);

        return response()->json($seccion, 201);
    }

    public function show(Seccion $seccion)
    {
        return $seccion;
    }

    public function update(Request $request, Seccion $seccion)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:20|unique:seccion,nombre,' . $seccion->id_seccion . ',id_seccion',
        ]);

        $seccion->update($validated);

        return response()->json($seccion);
    }

    public function destroy(Seccion $seccion)
    {
        return $this->deleteWithGuard(
            $seccion,
            fn ($s) => $s->asignaciones()->exists(),
            'No se puede eliminar la sección porque tiene asignaciones asociadas.'
        );
    }
}
