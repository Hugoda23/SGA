<?php

namespace App\Http\Middleware;

use App\Models\Configuracion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta todas las rutas autenticadas con 503 cuando el modo mantenimiento
 * está activo (ver Configuracion::mantenimiento_activo), salvo para quien
 * tenga el permiso "mantenimiento.ver" — el rol admin lo tiene siempre
 * (incluido en 'all'), así que sigue trabajando con normalidad. Además, se
 * le puede otorgar puntualmente a otra cuenta puntual desde Permisos por
 * Usuario, para poder probar algo como director/catedrático/alumno
 * mientras el resto queda afuera. El login también bloquea a quien no lo
 * tenga (ver AuthController::login) para no dejarlo entrar y chocar con
 * esto en la primera petición.
 */
class CheckMantenimiento
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Configuracion::get('mantenimiento_activo', '0') !== '1') {
            return $next($request);
        }

        $usuario = $request->user();
        $tieneBypass = $usuario && in_array('mantenimiento.ver', $usuario->permisos, true);

        if ($tieneBypass) {
            return $next($request);
        }

        return response()->json([
            'message' => Configuracion::get('mantenimiento_mensaje') ?: 'El sistema está en mantenimiento. Volvé a intentarlo más tarde.',
            'mantenimiento' => true,
        ], 503);
    }
}
