<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshSanctumToken
{
    /**
     * Renueva el token de acceso cuando ya pasó la mitad de su vida útil,
     * para que un usuario activo nunca vea el login expirar.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $token = $user?->currentAccessToken();
        $expiration = (int) config('sanctum.expiration');

        if ($token && $token->created_at && $expiration > 0
            && $token->created_at->lt(now()->subMinutes(intdiv($expiration, 2)))
        ) {
            $newToken = $user->createToken($token->name ?: 'sga-token')->plainTextToken;
            $token->delete();

            $response->headers->set('X-New-Token', $newToken);
        }

        return $response;
    }
}
