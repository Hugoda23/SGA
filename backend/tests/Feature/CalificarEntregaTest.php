<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\EntregaTarea;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Tarea;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalificarEntregaTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoCatedratico(): Catedratico
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'catedratico']);
        $permiso = Permiso::create(['nombre' => 'entregas.calificar', 'descripcion' => 'Calificar entregas']);
        $rol->permisos()->attach($permiso->id_permiso);
        $usuario->roles()->attach($rol->id_rol);
        $catedratico = Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);

        $this->actingAs($usuario, 'sanctum');

        return $catedratico;
    }

    public function test_rechaza_calificacion_por_encima_de_los_puntos_de_la_tarea(): void
    {
        $catedratico = $this->actuarComoCatedratico();
        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);

        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 25]);
        $entrega = EntregaTarea::factory()->create(['id_tarea' => $tarea->id_tarea]);

        $response = $this->postJson("/api/v1/entregas-tarea/calificar/{$entrega->id_entrega}", [
            'calificacion' => 30,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('calificacion');
    }

    public function test_acepta_calificacion_dentro_de_los_puntos_de_la_tarea(): void
    {
        $catedratico = $this->actuarComoCatedratico();
        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);

        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 25]);
        $entrega = EntregaTarea::factory()->create(['id_tarea' => $tarea->id_tarea]);

        $response = $this->postJson("/api/v1/entregas-tarea/calificar/{$entrega->id_entrega}", [
            'calificacion' => 20,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(20, $entrega->fresh()->calificacion);
    }

    public function test_tarea_sin_puntos_definidos_usa_tope_de_100(): void
    {
        $catedratico = $this->actuarComoCatedratico();
        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);

        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => null]);
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

    public function test_calificar_una_tarea_vinculada_a_zona_actualiza_la_nota_final(): void
    {
        $catedratico = $this->actuarComoCatedratico();
        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);

        $zona = \App\Models\ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'puntos' => 30]);
        $inscripcion = \App\Models\Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);
        $entrega = EntregaTarea::factory()->create(['id_tarea' => $tarea->id_tarea, 'id_alumno' => $inscripcion->id_alumno]);

        $response = $this->postJson("/api/v1/entregas-tarea/calificar/{$entrega->id_entrega}", [
            'calificacion' => 30,
        ]);

        $response->assertStatus(200);
        $notaFinal = (float) $inscripcion->calificacionesFinales()->first()->nota_final;
        $this->assertEquals(100.0, $notaFinal);
    }
}
