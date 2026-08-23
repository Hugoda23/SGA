<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Edificio;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;

class EdificioController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index()
    {
        return Edificio::with('aulas')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'ubicacion' => 'nullable|string|max:255',
        ]);

        $edificio = Edificio::create($validated);

        return response()->json($edificio, 201);
    }

    public function show(Edificio $edificio)
    {
        return $edificio->load('aulas');
    }

    public function update(Request $request, Edificio $edificio)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'ubicacion' => 'nullable|string|max:255',
        ]);

        $edificio->update($validated);

        return response()->json($edificio);
    }

    public function destroy(Edificio $edificio)
    {
        return $this->deleteWithGuard(
            $edificio,
            fn ($e) => $e->aulas()->whereHas('asignaciones')->exists(),
            'No se puede eliminar el edificio porque tiene aulas asociadas a asignaciones.'
        );
    }
}
