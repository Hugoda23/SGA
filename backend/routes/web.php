<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Verificación pública de documentos generados por el SGA
Route::get('/verificar/{token}', [App\Http\Controllers\VerificacionController::class, 'verificar'])
    ->name('verificacion.documento');
