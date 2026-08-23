<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(string $rolNombre, string $password = 'Password1', string $estado = 'activo'): Usuario
    {
        $usuario = Usuario::factory()->create([
            'password' => Hash::make($password),
            'estado' => $estado,
        ]);

        $rol = Rol::factory()->create(['nombre' => $rolNombre]);
        $usuario->roles()->attach($rol->id_rol);

        return $usuario;
    }

    public function test_login_correcto_devuelve_token(): void
    {
        $usuario = $this->usuarioConRol('admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'codigo' => $usuario->username,
            'password' => 'Password1',
            'tipo' => 'administrador',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['token', 'usuario', 'password_change_required']);

        $this->assertDatabaseHas('bitacora', [
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'INICIAR SESIÓN',
        ]);
    }

    public function test_login_rechaza_tipo_incompatible_con_el_rol(): void
    {
        $usuario = $this->usuarioConRol('alumno');

        $response = $this->postJson('/api/v1/auth/login', [
            'codigo' => $usuario->username,
            'password' => 'Password1',
            'tipo' => 'administrador',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('tipo');
    }

    public function test_login_rechaza_credenciales_invalidas_y_registra_bitacora(): void
    {
        $usuario = $this->usuarioConRol('admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'codigo' => $usuario->username,
            'password' => 'contrasena-incorrecta',
            'tipo' => 'administrador',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('codigo');

        $this->assertDatabaseHas('bitacora', [
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'INTENTO FALLIDO',
        ]);
    }

    public function test_login_rechaza_cuenta_desactivada_y_registra_bitacora(): void
    {
        $usuario = $this->usuarioConRol('admin', estado: 'inactivo');

        $response = $this->postJson('/api/v1/auth/login', [
            'codigo' => $usuario->username,
            'password' => 'Password1',
            'tipo' => 'administrador',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('codigo');

        $this->assertDatabaseHas('bitacora', [
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'LOGIN RECHAZADO',
        ]);
    }

    public function test_cambio_de_password_revoca_los_demas_tokens(): void
    {
        $usuario = $this->usuarioConRol('admin');

        $tokenActual = $usuario->createToken('sesion-actual')->plainTextToken;
        $usuario->createToken('otra-sesion');

        $this->assertSame(2, $usuario->tokens()->count());

        $response = $this->withHeader('Authorization', "Bearer {$tokenActual}")
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'Password1',
                'new_password' => 'NuevaClave2',
                'new_password_confirmation' => 'NuevaClave2',
            ]);

        $response->assertStatus(200);

        $this->assertSame(1, $usuario->tokens()->count());
        $this->assertTrue(Hash::check('NuevaClave2', $usuario->fresh()->password));
    }
}
