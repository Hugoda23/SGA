<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function index()
    {
        return Configuracion::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'clave' => 'required|string|max:100',
            'valor' => 'nullable|string',
        ]);

        $config = Configuracion::create($validated);

        return response()->json($config, 201);
    }

    public function show(Configuracion $configuracion)
    {
        return $configuracion;
    }

    public function update(Request $request, Configuracion $configuracion)
    {
        $validated = $request->validate([
            'clave' => 'sometimes|string|max:100',
            'valor' => 'nullable|string',
        ]);

        $configuracion->update($validated);

        return response()->json($configuracion);
    }

    public function destroy(Configuracion $configuracion)
    {
        $configuracion->delete();

        return response()->json(null, 204);
    }
}
