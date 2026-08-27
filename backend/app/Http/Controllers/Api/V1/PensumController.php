<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pensum;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class PensumController extends Controller
{
    public function index()
    {
        return Pensum::with('carrera', 'curso', 'grado')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_carrera' => 'nullable|exists:carrera,id_carrera',
            'id_curso' => 'required|exists:curso,id_curso',
            'id_grado' => 'nullable|exists:grado,id_grado',
            'obligatorio' => 'boolean',
        ]);

        try {
            $pensum = Pensum::create($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                return response()->json(['message' => 'Ya existe un pensum con esta carrera, curso y grado.'], 409);
            }

            throw $e;
        }

        return response()->json($pensum, 201);
    }

    public function show(Pensum $pensum)
    {
        return $pensum->load('carrera', 'curso', 'grado');
    }

    public function update(Request $request, Pensum $pensum)
    {
        $validated = $request->validate([
            'id_carrera' => 'sometimes|nullable|exists:carrera,id_carrera',
            'id_curso' => 'sometimes|exists:curso,id_curso',
            'id_grado' => 'nullable|exists:grado,id_grado',
            'obligatorio' => 'boolean',
        ]);

        try {
            $pensum->update($validated);
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                return response()->json(['message' => 'Ya existe un pensum con esta carrera, curso y grado.'], 409);
            }

            throw $e;
        }

        return response()->json($pensum);
    }

    public function destroy(Pensum $pensum)
    {
        $pensum->delete();

        return response()->json(null, 204);
    }
}
