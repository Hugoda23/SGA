<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\Evaluacion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Tarea;
use App\Models\Usuario;
use App\Models\ZonaEvaluacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TareaZonaCapacidadTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoCatedratico(): void
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'catedratico']);
        $permiso = Permiso::create(['nombre' => 'tareas', 'descripcion' => 'Tareas']);
        $rol->permisos()->attach($permiso->id_permiso);
        $usuario->roles()->attach($rol->id_rol);
        Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);

        $this->actingAs($usuario, 'sanctum');
    }

    public function test_rechaza_tarea_que_excede_los_puntos_disponibles_de_la_zona(): void
    {
        $this->actuarComoCatedratico();

        $asignacion = Asignacion::factory()->create();
        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'porcentaje' => 25]);

        // Solo quedan 5 pts disponibles en la zona; se intenta crear una tarea de 10.
        $response = $this->postJson('/api/v1/tareas', [
            'titulo' => 'Tarea que no cabe',
            'puntos' => 10,
            'id_zona' => $zona->id_zona,
            'id_asignacion' => $asignacion->id_asignacion,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('puntos');
        $this->assertDatabaseMissing('tarea', ['titulo' => 'Tarea que no cabe']);
    }

    public function test_permite_tarea_que_cabe_en_los_puntos_disponibles_de_la_zona(): void
    {
        $this->actuarComoCatedratico();

        $asignacion = Asignacion::factory()->create();
        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'porcentaje' => 20]);

        // Quedan 10 pts disponibles; la tarea pide exactamente eso.
        $response = $this->postJson('/api/v1/tareas', [
            'titulo' => 'Tarea que sí cabe',
            'puntos' => 10,
            'id_zona' => $zona->id_zona,
            'id_asignacion' => $asignacion->id_asignacion,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tarea', ['titulo' => 'Tarea que sí cabe', 'id_zona' => $zona->id_zona]);
    }

    public function test_considera_otras_tareas_ya_asignadas_a_la_misma_zona(): void
    {
        $this->actuarComoCatedratico();

        $asignacion = Asignacion::factory()->create();
        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'puntos' => 25]);

        // Ya hay 25 pts consumidos por otra tarea; solo quedan 5.
        $response = $this->postJson('/api/v1/tareas', [
            'titulo' => 'Segunda tarea de la zona',
            'puntos' => 10,
            'id_zona' => $zona->id_zona,
            'id_asignacion' => $asignacion->id_asignacion,
        ]);

        $response->assertStatus(422);
    }

    public function test_requiere_zona_y_puntos_para_crear_una_tarea(): void
    {
        $this->actuarComoCatedratico();

        $asignacion = Asignacion::factory()->create();

        $response = $this->postJson('/api/v1/tareas', [
            'titulo' => 'Tarea sin zona',
            'id_asignacion' => $asignacion->id_asignacion,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_zona', 'puntos']);
    }

    public function test_al_editar_una_tarea_no_se_cuenta_a_si_misma_como_consumo(): void
    {
        $this->actuarComoCatedratico();

        $asignacion = Asignacion::factory()->create();
        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'puntos' => 20]);

        // Bajar de 20 a 25 pts sigue cabiendo (20 disponibles + los 20 que ya tenía = 30 - 0 de otras).
        $response = $this->putJson("/api/v1/tareas/{$tarea->id_tarea}", [
            'puntos' => 25,
            'id_zona' => $zona->id_zona,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(25, $tarea->fresh()->puntos);
    }
}
