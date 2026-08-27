<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permiso' => \App\Http\Middleware\EnsurePermiso::class,
            'refresh.token' => \App\Http\Middleware\RefreshSanctumToken::class,
            'mantenimiento' => \App\Http\Middleware\CheckMantenimiento::class,
        ]);

        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(fn (Request $request) => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return $request->is('api/*')
                ? response()->json(['message' => 'No autenticado.'], 401)
                : response()->noContent(401);
        });
    })->create();
