<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Configuracion;
use App\Models\Usuario;
use App\Rules\SecurePassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string',
            'password' => 'required|string',
            'tipo' => 'required|string|in:administrador,docente,estudiante',
        ]);

        $roleMap = [
            'administrador' => ['admin', 'director', 'secretaria'],
            'docente' => ['catedratico'],
            'estudiante' => ['alumno'],
        ];

        $usuario = Usuario::where('username', $request->codigo)->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->password)) {
            $this->logSeguridad(
                $usuario?->id_usuario,
                'INTENTO FALLIDO',
                'usuario',
                $usuario?->id_usuario,
                "Intento fallido de inicio de sesión con el código '{$request->codigo}'."
            );

            throw ValidationException::withMessages([
                'codigo' => ['El código o la contraseña son incorrectos.'],
            ]);
        }

        if ($usuario->estado === 'inactivo') {
            $this->logSeguridad(
                $usuario->id_usuario,
                'LOGIN RECHAZADO',
                'usuario',
                $usuario->id_usuario,
                'Intento de inicio de sesión con cuenta desactivada.'
            );

            throw ValidationException::withMessages([
                'codigo' => ['La cuenta está desactivada.'],
            ]);
        }

        $usuario->load('roles.permisos', 'permisosPropios', 'alumno', 'catedratico');
        $allowedRoles = $roleMap[$request->tipo];

        if (!$usuario->roles->pluck('nombre')->intersect($allowedRoles)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'tipo' => ['El usuario no tiene un rol compatible con la selección.'],
            ]);
        }

        $tieneBypass = in_array('mantenimiento.ver', $usuario->permisos, true);
        if (!$tieneBypass && Configuracion::get('mantenimiento_activo', '0') === '1') {
            throw ValidationException::withMessages([
                'codigo' => [Configuracion::get('mantenimiento_mensaje') ?: 'El sistema está en mantenimiento. Volvé a intentarlo más tarde.'],
            ]);
        }

        $token = $usuario->createToken('sga-token')->plainTextToken;

        $this->logSeguridad(
            $usuario->id_usuario,
            'INICIAR SESIÓN',
            'usuario',
            $usuario->id_usuario,
            "Inicio de sesión exitoso para '{$usuario->username}'."
        );

        return response()->json([
            'token' => $token,
            'usuario' => $usuario,
            'password_change_required' => (bool) $usuario->password_change_required,
        ]);
    }

    public function logout(Request $request)
    {
        $usuario = $request->user();
        $token = $usuario->currentAccessToken();
        $tokenId = $token?->id;

        $token?->delete();

        $this->logSeguridad(
            $usuario->id_usuario,
            'CERRAR SESIÓN',
            'usuario',
            $usuario->id_usuario,
            "Cierre de sesión para '{$usuario->username}' (token #{$tokenId})."
        );

        return response()->json(['message' => 'Sesión cerrada exitosamente.']);
    }

    public function me(Request $request)
    {
        $usuario = $request->user()->load('roles.permisos', 'alumno', 'catedratico');

        return response()->json($usuario);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'max:255', 'confirmed', new SecurePassword],
            'new_password_confirmation' => 'required|string',
        ]);

        $usuario = $request->user();

        if (!Hash::check($request->current_password, $usuario->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $usuario->update([
            'password' => bcrypt($request->new_password),
            'password_change_required' => false,
        ]);

        $usuario->tokens()->where('id', '!=', $usuario->currentAccessToken()->id)->delete();

        $this->logSeguridad(
            $usuario->id_usuario,
            'CAMBIAR CONTRASEÑA',
            'usuario',
            $usuario->id_usuario,
            "El usuario '{$usuario->username}' cambió su contraseña (otras sesiones revocadas)."
        );

        return response()->json(['message' => 'Contraseña cambiada exitosamente.']);
    }

    private function logSeguridad(?int $idUsuario, string $accion, string $tabla, ?int $idRegistro, string $descripcion): void
    {
        try {
            Bitacora::create([
                'id_usuario' => $idUsuario,
                'accion' => $accion,
                'tabla_afectada' => $tabla,
                'id_registro' => $idRegistro,
                'descripcion' => $descripcion,
                'fecha_hora' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
