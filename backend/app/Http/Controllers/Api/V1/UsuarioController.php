<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Rules\SecurePassword;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index()
    {
        return Usuario::with('roles', 'alumno', 'catedratico')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:100|unique:usuario,username',
            'password' => ['required', 'string', 'max:255', new SecurePassword],
            'estado' => 'nullable|string|max:50',
        ]);

        $validated['password'] = bcrypt($validated['password']);

        $usuario = Usuario::create($validated);

        return response()->json($usuario, 201);
    }

    public function show(Usuario $usuario)
    {
        return $usuario->load('roles', 'alumno', 'catedratico');
    }

    public function update(Request $request, Usuario $usuario)
    {
        // El cambio de contraseña tiene su propio endpoint dedicado
        // (UserManagementController::updatePassword / PUT usuarios/{id}/password),
        // que además revoca los tokens existentes del usuario. Este método no
        // acepta "password" a propósito: un segundo camino para cambiarla aquí
        // (que además nunca llegó a persistirse — quedaba calculado y sin usar)
        // permitiría cambiarla sin esa revocación de tokens.
        $validated = $request->validate([
            'username' => 'sometimes|string|max:100|unique:usuario,username,' . $usuario->id_usuario . ',id_usuario',
            'estado' => 'nullable|string|max:50',
            'nombre' => 'nullable|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'correo' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:20',
        ]);

        $usuario->update([
            'username' => $validated['username'] ?? $usuario->username,
            'estado' => $validated['estado'] ?? $usuario->estado,
        ]);

        if ($usuario->alumno) {
            $usuario->alumno->update([
                'nombre' => $validated['nombre'] ?? $usuario->alumno->nombre,
                'apellido' => $validated['apellido'] ?? $usuario->alumno->apellido,
                'correo' => $validated['correo'] ?? $usuario->alumno->correo,
                'telefono' => $validated['telefono'] ?? $usuario->alumno->telefono,
            ]);
        } elseif ($usuario->catedratico) {
            $usuario->catedratico->update([
                'nombre' => $validated['nombre'] ?? $usuario->catedratico->nombre,
                'apellido' => $validated['apellido'] ?? $usuario->catedratico->apellido,
                'correo' => $validated['correo'] ?? $usuario->catedratico->correo,
                'telefono' => $validated['telefono'] ?? $usuario->catedratico->telefono,
            ]);
        }

        return response()->json($usuario->load('roles', 'alumno', 'catedratico'));
    }

    public function destroy(Usuario $usuario)
    {
        return $this->deleteWithGuard(
            $usuario,
            null,
            'No se puede eliminar el usuario porque tiene registros asociados. Se recomienda cambiar su estado a Inactivo.'
        );
    }
}
