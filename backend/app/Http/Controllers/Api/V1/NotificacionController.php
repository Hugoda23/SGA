<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index()
    {
        return Notificacion::with('usuario')->orderBy('fecha', 'desc')->get();
    }

    public function misNotificaciones(Request $request)
    {
        $usuario = $request->user();
        return Notificacion::where('id_usuario', $usuario->id_usuario)
            ->orderBy('fecha', 'desc')
            ->get();
    }

    public function noLeidas(Request $request)
    {
        $usuario = $request->user();
        return Notificacion::where('id_usuario', $usuario->id_usuario)
            ->where('leido', false)
            ->orderBy('fecha', 'desc')
            ->get();
    }

    public function marcarLeido(Request $request, Notificacion $notificacion)
    {
        $usuario = $request->user();
        if ($notificacion->id_usuario !== $usuario->id_usuario) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        $notificacion->update(['leido' => true]);
        return response()->json($notificacion);
    }

    public function marcarTodasLeidas(Request $request)
    {
        $usuario = $request->user();
        Notificacion::where('id_usuario', $usuario->id_usuario)
            ->where('leido', false)
            ->update(['leido' => true]);
        return response()->json(['message' => 'Todas marcadas como leídas.']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => 'required|exists:usuario,id_usuario',
            'mensaje' => 'required|string',
            'leido' => 'boolean',
        ]);

        $notificacion = Notificacion::create($validated);

        return response()->json($notificacion, 201);
    }

    public function show(Notificacion $notificacion)
    {
        return $notificacion->load('usuario');
    }

    public function update(Request $request, Notificacion $notificacion)
    {
        $validated = $request->validate([
            'mensaje' => 'sometimes|string',
            'leido' => 'boolean',
        ]);

        $notificacion->update($validated);

        return response()->json($notificacion);
    }

    public function destroy(Notificacion $notificacion)
    {
        $notificacion->delete();
        return response()->json(null, 204);
    }
}
