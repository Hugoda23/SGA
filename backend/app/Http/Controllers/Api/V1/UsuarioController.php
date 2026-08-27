<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Permiso;
use App\Models\Usuario;
use App\Rules\SecurePassword;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Solo el personal sin perfil de Alumno/Catedratico (admin, director,
        // secretaria) guarda su nombre/apellido en el propio Usuario; para
        // los que sí tienen perfil, esos campos viven ahí (ver abajo) y son
        // la fuente real que se muestra en toda la app.
        $tieneProfile = $usuario->alumno || $usuario->catedratico;

        $usuario->update([
            'username' => $validated['username'] ?? $usuario->username,
            'estado' => $validated['estado'] ?? $usuario->estado,
            'nombre' => $tieneProfile ? $usuario->nombre : ($validated['nombre'] ?? $usuario->nombre),
            'apellido' => $tieneProfile ? $usuario->apellido : ($validated['apellido'] ?? $usuario->apellido),
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

    /**
     * GET /v1/usuarios/{usuario}/permisos
     * Lista todos los permisos del sistema indicando, para este usuario en
     * particular: si lo hereda de alguno de sus roles, si tiene una
     * excepción propia (override) y cuál es el valor efectivo resultante
     * (lo que realmente controla sus vistas y accesos).
     */
    public function permisos(Usuario $usuario)
    {
        $usuario->load('roles.permisos', 'permisosPropios');

        $idsDeRoles = $usuario->roles
            ->flatMap(fn ($rol) => $rol->permisos->pluck('id_permiso'))
            ->unique();

        $overrides = $usuario->permisosPropios->keyBy('id_permiso');

        $permisos = Permiso::orderBy('nombre')->get()->map(function ($permiso) use ($idsDeRoles, $overrides) {
            $heredado = $idsDeRoles->contains($permiso->id_permiso);
            $override = $overrides->get($permiso->id_permiso);
            $tieneOverride = $override !== null;

            return [
                'id_permiso' => $permiso->id_permiso,
                'nombre' => $permiso->nombre,
                'descripcion' => $permiso->descripcion,
                'heredado' => $heredado,
                'override' => $tieneOverride ? (bool) $override->pivot->concedido : null,
                'efectivo' => $tieneOverride ? (bool) $override->pivot->concedido : $heredado,
            ];
        });

        return response()->json($permisos);
    }

    /**
     * PUT /v1/usuarios/{usuario}/permisos
     * Reemplaza las excepciones de permisos propias del usuario (no toca lo
     * que le dan sus roles). Body: { overrides: [{ id_permiso, concedido }] }
     */
    public function syncPermisosPropios(Request $request, Usuario $usuario)
    {
        $validated = $request->validate([
            'overrides' => 'array',
            'overrides.*.id_permiso' => 'required|integer|exists:permiso,id_permiso',
            'overrides.*.concedido' => 'required|boolean',
        ]);

        $sync = [];
        foreach ($validated['overrides'] ?? [] as $item) {
            $sync[$item['id_permiso']] = ['concedido' => $item['concedido']];
        }

        $usuario->permisosPropios()->sync($sync);

        if (Auth::id()) {
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'accion' => 'ACTUALIZAR',
                'tabla_afectada' => 'usuario_permiso',
                'id_registro' => $usuario->id_usuario,
                'descripcion' => "Se actualizaron las excepciones de permisos del usuario \"{$usuario->username}\".",
                'fecha_hora' => now(),
            ]);
        }

        return response()->json(['message' => 'Permisos del usuario actualizados correctamente.']);
    }
}
