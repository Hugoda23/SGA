<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePermiso
{
    /**
     * Valida que el usuario autenticado tenga el permiso requerido
     * (o un permiso del mismo módulo, ej. "usuarios" cubre "usuarios.ver").
     */
    public function handle(Request $request, Closure $next, string $permiso)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $permisos = $user->permisos ?? [];

        $cubre = in_array($permiso, $permisos, true)
            || collect($permisos)->contains(fn ($p) => str_starts_with($p, $permiso . '.'));

        if (!$cubre) {
            return response()->json([
                'message' => 'No tiene permiso para realizar esta acción.',
                'permiso' => $permiso,
            ], 403);
        }

        return $next($request);
    }
}
