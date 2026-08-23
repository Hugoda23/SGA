<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
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

    private function crearRolesStaff(): void
    {
        foreach (['admin', 'director', 'secretaria'] as $nombre) {
            Rol::firstOrCreate(['nombre' => $nombre]);
        }
    }

    public function test_crea_un_usuario_staff(): void
    {
        $this->actuarComoAdmin();
        $this->crearRolesStaff();

        $response = $this->postJson('/api/v1/usuarios/admin', [
            'username' => 'nuevo.director',
            'password' => 'Password123',
            'rol' => 'director',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('usuario', ['username' => 'nuevo.director']);
    }

    public function test_rechaza_contrasena_debil(): void
    {
        $this->actuarComoAdmin();
        $this->crearRolesStaff();

        $response = $this->postJson('/api/v1/usuarios/admin', [
            'username' => 'usuario.debil',
            'password' => 'sololetras',
            'rol' => 'secretaria',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('password');
        $this->assertDatabaseMissing('usuario', ['username' => 'usuario.debil']);
    }

    public function test_rechaza_username_duplicado(): void
    {
        $this->actuarComoAdmin();
        $this->crearRolesStaff();
        Usuario::factory()->create(['username' => 'repetido']);

        $response = $this->postJson('/api/v1/usuarios/admin', [
            'username' => 'repetido',
            'password' => 'Password123',
            'rol' => 'secretaria',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_actualizar_password_revoca_los_tokens_existentes(): void
    {
        $this->actuarComoAdmin();
        $objetivo = Usuario::factory()->create();
        $objetivo->createToken('sesion-anterior');
        $this->assertCount(1, $objetivo->tokens);

        $response = $this->putJson("/api/v1/usuarios/{$objetivo->id_usuario}/password", [
            'password' => 'NuevaPassword123',
        ]);

        $response->assertStatus(200);
        $this->assertCount(0, $objetivo->fresh()->tokens);
        $this->assertTrue((bool) $objetivo->fresh()->password_change_required);
    }

    public function test_desactivar_usuario_revoca_sus_tokens(): void
    {
        $this->actuarComoAdmin();
        $objetivo = Usuario::factory()->create(['estado' => 'activo']);
        $objetivo->createToken('sesion-anterior');

        $response = $this->patchJson("/api/v1/usuarios/{$objetivo->id_usuario}/estado");

        $response->assertStatus(200)->assertJsonPath('estado', 'inactivo');
        $this->assertCount(0, $objetivo->fresh()->tokens);
    }
}
