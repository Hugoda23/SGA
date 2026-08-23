<?php

namespace Tests\Feature;

use App\Models\Catedratico;
use App\Models\EntregaTarea;
use App\Models\Rol;
use App\Models\Tarea;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalificarEntregaTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoCatedratico(): Usuario
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'catedratico']);
        $usuario->roles()->attach($rol->id_rol);
        Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);

        $this->actingAs($usuario, 'sanctum');

        return $usuario;
    }

    public function test_rechaza_calificacion_por_encima_de_los_puntos_de_la_tarea(): void
    {
        $this->actuarComoCatedratico();

        $tarea = Tarea::factory()->create(['puntos' => 25]);
        $entrega = EntregaTarea::factory()->create(['id_tarea' => $tarea->id_tarea]);

        $response = $this->postJson("/api/v1/entregas-tarea/calificar/{$entrega->id_entrega}", [
            'calificacion' => 30,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('calificacion');
    }

    public function test_acepta_calificacion_dentro_de_los_puntos_de_la_tarea(): void
    {
        $this->actuarComoCatedratico();

        $tarea = Tarea::factory()->create(['puntos' => 25]);
        $entrega = EntregaTarea::factory()->create(['id_tarea' => $tarea->id_tarea]);

        $response = $this->postJson("/api/v1/entregas-tarea/calificar/{$entrega->id_entrega}", [
            'calificacion' => 20,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(20, $entrega->fresh()->calificacion);
    }

    public function test_tarea_sin_puntos_definidos_usa_tope_de_100(): void
    {
        $this->actuarComoCatedratico();

        $tarea = Tarea::factory()->create(['puntos' => null]);
        $entrega = EntregaTarea::factory()->create(['id_tarea' => $tarea->id_tarea]);

        $rechazado = $this->postJson("/api/v1/entregas-tarea/calificar/{$entrega->id_entrega}", [
            'calificacion' => 150,
        ]);
        $rechazado->assertStatus(422);

        $aceptado = $this->postJson("/api/v1/entregas-tarea/calificar/{$entrega->id_entrega}", [
            'calificacion' => 90,
        ]);
        $aceptado->assertStatus(200);
    }
}
