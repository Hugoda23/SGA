<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermisoControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoAdmin(): void
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'admin']);
        $permiso = Permiso::create(['nombre' => 'permisos', 'descripcion' => 'Permisos']);
        $rol->permisos()->attach($permiso->id_permiso);
        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');
    }

    public function test_seed_defaults_crea_todos_los_permisos_por_defecto(): void
    {
        // actuarComoAdmin() ya crea un permiso ("permisos", sin notación de
        // punto) para poder llamar al endpoint — no es parte de defaults().
        $this->actuarComoAdmin();
        $totalDefaults = count(Permiso::defaults());
        $totalAntes = Permiso::count();

        $response = $this->postJson('/api/v1/permisos/seed');

        $response->assertStatus(200)->assertJsonPath('total', $totalDefaults);
        $this->assertEquals($totalAntes + $totalDefaults, Permiso::count());
    }

    public function test_seed_defaults_es_idempotente_y_no_duplica_ni_reporta_de_mas(): void
    {
        $this->actuarComoAdmin();
        $this->postJson('/api/v1/permisos/seed')->assertStatus(200);
        $totalDespuesDePrimeraCarga = Permiso::count();

        // Segunda llamada: no hay nada nuevo que crear, "total" debe ser 0,
        // no el conteo completo otra vez (bug encontrado: antes contaba
        // como "creado" cada permiso ya existente).
        $response = $this->postJson('/api/v1/permisos/seed');

        $response->assertStatus(200)->assertJsonPath('total', 0);
        $this->assertEquals($totalDespuesDePrimeraCarga, Permiso::count());
    }
}
