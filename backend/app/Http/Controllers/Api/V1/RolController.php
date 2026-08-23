<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Rol;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index()
    {
        return Rol::with('permisos')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        $rol = Rol::create($validated);

        return response()->json($rol, 201);
    }

    public function show(Rol $rol)
    {
        return $rol->load('permisos');
    }

    public function update(Request $request, Rol $rol)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        $rol->update($validated);

        return response()->json($rol);
    }

    public function destroy(Rol $rol)
    {
        return $this->deleteWithGuard(
            $rol,
            fn ($r) => $r->usuarios()->exists() || $r->permisos()->exists(),
            'No se puede eliminar el rol porque está asignado a usuarios o tiene permisos asociados.'
        );
    }

    /**
     * POST /v1/roles/{rol}/permisos
     * Sincroniza los permisos asignados al rol.
     * Body: { permiso_ids: [1, 2, 3] }
     */
    public function syncPermisos(Request $request, Rol $rol)
    {
        $validated = $request->validate([
            'permiso_ids' => 'array',
            'permiso_ids.*' => 'integer|exists:permiso,id_permiso',
        ]);

        // sync() sobre la tabla pivote rol_permiso no dispara los eventos
        // Eloquent (created/updated/deleted) que usa LogsActivity, así que
        // reasignar los permisos de un rol quedaría sin rastro en la
        // bitácora si no se registra explícitamente aquí.
        $idsAnteriores = $rol->permisos()->pluck('permiso.id_permiso')->all();
        $idsNuevos = $validated['permiso_ids'] ?? [];

        $rol->permisos()->sync($idsNuevos);

        if (Auth::id()) {
            sort($idsAnteriores);
            sort($idsNuevos);
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'accion' => 'ACTUALIZAR',
                'tabla_afectada' => 'rol_permiso',
                'id_registro' => $rol->id_rol,
                'descripcion' => "Se reasignaron los permisos del rol \"{$rol->nombre}\": [" . implode(',', $idsAnteriores) . '] -> [' . implode(',', $idsNuevos) . ']',
                'fecha_hora' => now(),
            ]);
        }

        return response()->json($rol->load('permisos'));
    }
}
