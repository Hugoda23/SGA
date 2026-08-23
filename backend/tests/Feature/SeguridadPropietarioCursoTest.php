<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\ZonaEvaluacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug de seguridad encontrado y corregido: verificarCatedratico() (antes
 * duplicado en 7 controladores) dejaba pasar la petición cuando el usuario
 * autenticado NO tenía perfil de catedrático — la intención era permitir
 * el acceso a admins, pero un ALUMNO tampoco tiene perfil de catedrático,
 * así que también entraba. Cualquier alumno con token válido podía
 * crear/editar/borrar la estructura de evaluación de un curso ajeno.
 */
class SeguridadPropietarioCursoTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComo(string $rolNombre): Usuario
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => $rolNombre]);
        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');

        return $usuario;
    }

    public function test_alumno_no_puede_crear_unidad_en_curso_ajeno(): void
    {
        $this->actuarComo('alumno');
        $asignacion = Asignacion::factory()->create();

        $response = $this->postJson('/api/v1/unidades', [
            'id_asignacion' => $asignacion->id_asignacion,
            'titulo' => 'Unidad intrusa',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('unidad', ['titulo' => 'Unidad intrusa']);
    }

    public function test_alumno_no_puede_crear_zona_en_curso_ajeno(): void
    {
        $this->actuarComo('alumno');
        $asignacion = Asignacion::factory()->create();

        $response = $this->postJson('/api/v1/zonas', [
            'id_asignacion' => $asignacion->id_asignacion,
            'nombre' => 'Zona intrusa',
            'puntos' => 30,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('zona_evaluacion', ['nombre' => 'Zona intrusa']);
    }

    public function test_alumno_no_puede_crear_anuncio_en_curso_ajeno(): void
    {
        $this->actuarComo('alumno');
        $asignacion = Asignacion::factory()->create();

        $response = $this->postJson('/api/v1/anuncios', [
            'id_asignacion' => $asignacion->id_asignacion,
            'titulo' => 'Anuncio intruso',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('anuncio', ['titulo' => 'Anuncio intruso']);
    }

    public function test_alumno_no_puede_crear_material_en_curso_ajeno(): void
    {
        $this->actuarComo('alumno');
        $asignacion = Asignacion::factory()->create();

        $response = $this->postJson('/api/v1/materiales', [
            'id_asignacion' => $asignacion->id_asignacion,
            'titulo' => 'Material intruso',
            'tipo' => 'enlace',
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('material', ['titulo' => 'Material intruso']);
    }

    public function test_alumno_no_puede_guardar_calificaciones_de_curso_ajeno(): void
    {
        $this->actuarComo('alumno');
        $asignacion = Asignacion::factory()->create();

        $response = $this->postJson("/api/v1/registro-calificaciones/{$asignacion->id_asignacion}/guardar", [
            'notas' => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_alumno_no_puede_crear_evaluacion_de_curso_ajeno(): void
    {
        $this->actuarComo('alumno');
        $asignacion = Asignacion::factory()->create();

        $response = $this->postJson("/api/v1/registro-calificaciones/{$asignacion->id_asignacion}/evaluaciones", [
            'nombre' => 'Evaluación intrusa',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('evaluacion', ['nombre' => 'Evaluación intrusa']);
    }

    public function test_catedratico_no_puede_gestionar_curso_de_otro_catedratico(): void
    {
        $usuario = $this->actuarComo('catedratico');
        Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $asignacionAjena = Asignacion::factory()->create(); // dueña de otro catedrático

        $response = $this->postJson('/api/v1/unidades', [
            'id_asignacion' => $asignacionAjena->id_asignacion,
            'titulo' => 'Unidad de curso ajeno',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_si_puede_gestionar_cualquier_curso(): void
    {
        $this->actuarComo('admin');
        $asignacion = Asignacion::factory()->create();

        $response = $this->postJson('/api/v1/unidades', [
            'id_asignacion' => $asignacion->id_asignacion,
            'titulo' => 'Unidad creada por admin',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('unidad', ['titulo' => 'Unidad creada por admin']);
    }

    public function test_catedratico_dueno_si_puede_gestionar_su_propio_curso(): void
    {
        $usuario = $this->actuarComo('catedratico');
        $catedratico = Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);

        $response = $this->postJson('/api/v1/zonas', [
            'id_asignacion' => $asignacion->id_asignacion,
            'nombre' => 'Zona propia',
            'puntos' => 30,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('zona_evaluacion', ['nombre' => 'Zona propia']);
    }
}
