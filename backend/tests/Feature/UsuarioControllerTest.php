<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UsuarioController::store aceptaba cualquier string como contraseña
 * (sin la regla SecurePassword que sí exige UserManagementController::store
 * para el mismo tipo de recurso) y ::update calculaba un bcrypt de
 * "password" que nunca llegaba a persistirse — un segundo camino,
 * silenciosamente roto, para cambiar la contraseña sin pasar por el
 * endpoint dedicado que revoca los tokens existentes.
 */
class UsuarioControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoAdmin(): void
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'admin']);
        $permiso = Permiso::create(['nombre' => 'usuarios', 'descripcion' => 'Usuarios']);
        $rol->permisos()->attach($permiso->id_permiso);
        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');
    }

    public function test_rechaza_contrasena_debil_al_crear_usuario(): void
    {
        $this->actuarComoAdmin();

        $response = $this->postJson('/api/v1/usuarios', [
            'username' => 'usuario.debil',
            'password' => 'sololetras',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_actualizar_usuario_no_cambia_la_contrasena(): void
    {
        $this->actuarComoAdmin();
        $objetivo = Usuario::factory()->create();
        $hashOriginal = $objetivo->password;

        $response = $this->putJson("/api/v1/usuarios/{$objetivo->id_usuario}", [
            'password' => 'NuevaPassword123',
        ]);

        $response->assertStatus(200);
        $this->assertEquals($hashOriginal, $objetivo->fresh()->password);
    }
}
