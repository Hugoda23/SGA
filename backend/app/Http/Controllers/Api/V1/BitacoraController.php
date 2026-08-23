<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    public function index()
    {
        return Bitacora::with('usuario')->orderBy('fecha_hora', 'desc')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => 'nullable|exists:usuario,id_usuario',
            'accion' => 'required|string|max:255',
            'tabla_afectada' => 'nullable|string|max:100',
            'id_registro' => 'nullable|integer',
            'descripcion' => 'nullable|string',
        ]);

        $bitacora = Bitacora::create($validated);

        return response()->json($bitacora, 201);
    }

    public function show(Bitacora $bitacora)
    {
        return $bitacora->load('usuario');
    }

    public function destroy(Bitacora $bitacora)
    {
        $bitacora->delete();

        return response()->json(null, 204);
    }
}
