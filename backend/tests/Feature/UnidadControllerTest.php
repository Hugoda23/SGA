<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\Unidad;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnidadControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoCatedraticoDe(Asignacion $asignacion): void
    {
        $usuario = Usuario::factory()->create();
        $catedratico = Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $asignacion->update(['id_catedratico' => $catedratico->id_catedratico]);

        $this->actingAs($usuario, 'sanctum');
    }

    public function test_crea_una_unidad(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);

        $response = $this->postJson('/api/v1/unidades', [
            'id_asignacion' => $asignacion->id_asignacion,
            'numero_semana' => 1,
            'titulo' => 'Unidad 1: Introducción',
        ]);

        $response->assertStatus(201)->assertJsonPath('titulo', 'Unidad 1: Introducción');
        $this->assertDatabaseHas('unidad', ['id_asignacion' => $asignacion->id_asignacion, 'titulo' => 'Unidad 1: Introducción']);
    }

    public function test_rechaza_unidad_sin_titulo(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);

        $response = $this->postJson('/api/v1/unidades', [
            'id_asignacion' => $asignacion->id_asignacion,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('titulo');
    }

    public function test_actualiza_una_unidad(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);
        $unidad = Unidad::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'titulo' => 'Original']);

        $response = $this->putJson("/api/v1/unidades/{$unidad->id_unidad}", [
            'titulo' => 'Actualizada',
        ]);

        $response->assertStatus(200)->assertJsonPath('titulo', 'Actualizada');
    }

    public function test_elimina_una_unidad(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);
        $unidad = Unidad::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $response = $this->deleteJson("/api/v1/unidades/{$unidad->id_unidad}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('unidad', ['id_unidad' => $unidad->id_unidad]);
    }
}
