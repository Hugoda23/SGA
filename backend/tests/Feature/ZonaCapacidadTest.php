<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\CalificacionFinal;
use App\Models\Catedratico;
use App\Models\DetalleCalificacion;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Tarea;
use App\Models\Usuario;
use App\Models\ZonaEvaluacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZonaCapacidadTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoCatedratico(array $permisos): Catedratico
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'catedratico']);
        foreach ($permisos as $nombre) {
            $permiso = Permiso::create(['nombre' => $nombre, 'descripcion' => $nombre]);
            $rol->permisos()->attach($permiso->id_permiso);
        }
        $usuario->roles()->attach($rol->id_rol);
        $catedratico = Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);

        $this->actingAs($usuario, 'sanctum');

        return $catedratico;
    }

    public function test_no_permite_reducir_una_zona_por_debajo_de_lo_ya_asignado(): void
    {
        $catedratico = $this->actuarComoCatedratico(['evaluaciones']);

        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);
        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'porcentaje' => 30]);

        // Este es exactamente el bug reportado: bajar de 30 a 15 con 30 ya asignados.
        $response = $this->putJson("/api/v1/zonas/{$zona->id_zona}", ['puntos' => 15]);

        $response->assertStatus(422);
        $this->assertEquals(30, $zona->fresh()->puntos);
    }

    public function test_permite_reducir_una_zona_hasta_lo_que_ya_tiene_asignado(): void
    {
        $catedratico = $this->actuarComoCatedratico(['evaluaciones']);

        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);
        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'porcentaje' => 20]);

        $response = $this->putJson("/api/v1/zonas/{$zona->id_zona}", ['puntos' => 20]);

        $response->assertStatus(200);
        $this->assertEquals(20, $zona->fresh()->puntos);
    }

    public function test_considera_tareas_de_la_zona_al_reducir_sus_puntos(): void
    {
        $catedratico = $this->actuarComoCatedratico(['evaluaciones']);

        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);
        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'puntos' => 25]);

        $response = $this->putJson("/api/v1/zonas/{$zona->id_zona}", ['puntos' => 20]);

        $response->assertStatus(422);
    }

    public function test_rechaza_evaluacion_que_excede_los_puntos_disponibles_de_la_zona(): void
    {
        $catedratico = $this->actuarComoCatedratico(['evaluaciones']);

        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);
        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'porcentaje' => 25]);

        $response = $this->postJson("/api/v1/registro-calificaciones/{$asignacion->id_asignacion}/evaluaciones", [
            'id_zona' => $zona->id_zona,
            'nombre' => 'Actividad que no cabe',
            'porcentaje' => 10,
        ]);

        $response->assertStatus(422);
    }

    public function test_crear_una_zona_recalcula_la_nota_final(): void
    {
        $catedratico = $this->actuarComoCatedratico(['evaluaciones']);

        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        // Modo legado (sin zonas): una evaluación sin id_zona aporta directo a la nota final.
        $evaluacionLegado = Evaluacion::factory()->create([
            'id_asignacion' => $asignacion->id_asignacion,
            'id_zona' => null,
            'porcentaje' => 100,
        ]);
        DetalleCalificacion::factory()->create([
            'id_evaluacion' => $evaluacionLegado->id_evaluacion,
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'nota' => 80,
        ]);
        CalificacionFinal::factory()->create([
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'nota_final' => 80,
        ]);

        // Bug reportado: crear la primera zona del curso volvía autoritativa
        // la estructura por zonas (la evaluación sin zona deja de contar),
        // pero ZonaEvaluacionController::store() no recalculaba — nota_final
        // se quedaba con el valor legado (80) en vez de reflejar el cambio.
        $response = $this->postJson('/api/v1/zonas', [
            'id_asignacion' => $asignacion->id_asignacion,
            'nombre' => 'Zona 1',
            'puntos' => 30,
        ]);

        $response->assertStatus(201);
        $this->assertEquals(0, (float) $inscripcion->calificacionesFinales()->first()->nota_final);
    }
}
