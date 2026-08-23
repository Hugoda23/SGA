<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;

class CarreraController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index()
    {
        return Carrera::with('cursos', 'alumnos')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_carrera' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
        ]);

        $carrera = Carrera::create($validated);

        return response()->json($carrera, 201);
    }

    public function show(Carrera $carrera)
    {
        return $carrera->load('cursos', 'alumnos');
    }

    public function update(Request $request, Carrera $carrera)
    {
        $validated = $request->validate([
            'nombre_carrera' => 'sometimes|string|max:200',
            'descripcion' => 'nullable|string',
        ]);

        $carrera->update($validated);

        return response()->json($carrera);
    }

    public function destroy(Carrera $carrera)
    {
        return $this->deleteWithGuard(
            $carrera,
            fn ($c) => $c->alumnos()->exists(),
            'No se puede eliminar la carrera porque tiene alumnos asociados.'
        );
    }
}
