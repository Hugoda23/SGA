<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Grado;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;

class GradoController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index()
    {
        return Grado::orderBy('nivel')->orderBy('nombre')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:grado,nombre',
            'nivel'  => 'nullable|string|max:50',
        ]);

        $grado = Grado::create($validated);

        return response()->json($grado, 201);
    }

    public function show(Grado $grado)
    {
        return $grado;
    }

    public function update(Request $request, Grado $grado)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:50|unique:grado,nombre,' . $grado->id_grado . ',id_grado',
            'nivel'  => 'nullable|string|max:50',
        ]);

        $grado->update($validated);

        return response()->json($grado);
    }

    public function destroy(Grado $grado)
    {
        return $this->deleteWithGuard(
            $grado,
            fn ($g) => $g->asignaciones()->exists() || $g->pensums()->exists(),
            'No se puede eliminar el grado porque tiene asignaciones o pensum asociados.'
        );
    }
}
