<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Usuario;
use App\Rules\SecurePassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:100|unique:usuario,username',
            'password' => ['required', 'string', 'max:255', new SecurePassword],
            'rol' => 'required|string|in:admin,director,secretaria',
            'estado' => 'nullable|string|max:50',
        ]);

        return DB::transaction(function () use ($validated) {
            $usuario = Usuario::create([
                'username' => $validated['username'],
                'password' => bcrypt($validated['password']),
                'estado' => $validated['estado'] ?? 'activo',
            ]);

            $rol = Rol::where('nombre', $validated['rol'])->firstOrFail();
            $usuario->roles()->attach($rol->id_rol);

            $usuario->load('roles');

            return response()->json($usuario, 201);
        });
    }

    public function index()
    {
        return Usuario::with(['roles', 'alumno', 'catedratico'])->get();
    }

    public function updatePassword(Request $request, Usuario $usuario)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255', new SecurePassword],
        ]);

        $usuario->update([
            'password' => bcrypt($validated['password']),
            'password_change_required' => true,
        ]);

        $usuario->tokens()->delete();

        return response()->json(['message' => 'Contraseña actualizada exitosamente.']);
    }

    public function toggleStatus(Usuario $usuario)
    {
        $nuevoEstado = $usuario->estado === 'activo' ? 'inactivo' : 'activo';
        $usuario->update(['estado' => $nuevoEstado]);

        if ($nuevoEstado === 'inactivo') {
            $usuario->tokens()->delete();
        }

        return response()->json([
            'message' => "Usuario {$nuevoEstado}.",
            'estado' => $nuevoEstado,
        ]);
    }
}
