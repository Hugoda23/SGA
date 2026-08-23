<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permiso;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;

class PermisoController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index()
    {
        return Permiso::with('roles')->get();
    }

    public function seedDefaults()
    {
        $creados = 0;
        foreach (Permiso::defaults() as $permiso) {
            Permiso::firstOrCreate(
                ['nombre' => $permiso['nombre']],
                $permiso
            );
            $creados++;
        }

        return response()->json([
            'message' => 'Permisos por defecto cargados correctamente.',
            'total' => $creados,
            'data' => Permiso::with('roles')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        $permiso = Permiso::create($validated);

        return response()->json($permiso, 201);
    }

    public function show(Permiso $permiso)
    {
        return $permiso->load('roles');
    }

    public function update(Request $request, Permiso $permiso)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        $permiso->update($validated);

        return response()->json($permiso);
    }

    public function destroy(Permiso $permiso)
    {
        return $this->deleteWithGuard(
            $permiso,
            fn ($p) => $p->roles()->exists(),
            'No se puede eliminar el permiso porque está asignado a roles.'
        );
    }
}
