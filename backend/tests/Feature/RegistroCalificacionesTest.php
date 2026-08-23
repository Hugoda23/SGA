<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\DetalleCalificacion;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use App\Models\PeriodoAcademico;
use App\Models\Usuario;
use App\Models\ZonaEvaluacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RegistroCalificacionesController::guardar() es el flujo de "registrar una
 * nota" más usado del sistema — hasta ahora solo tenía cobertura de
 * autorización (el caso 403), nada verificaba que realmente guardara la
 * nota correcta ni que disparara el recálculo de nota_final como se espera.
 */
class RegistroCalificacionesTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoCatedraticoDe(Asignacion $asignacion): void
    {
        $usuario = Usuario::factory()->create();
        $catedratico = Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $asignacion->update(['id_catedratico' => $catedratico->id_catedratico]);

        $this->actingAs($usuario, 'sanctum');
    }

    public function test_guardar_notas_las_persiste_y_recalcula_la_nota_final_sin_zonas(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);
        $ev1 = Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => null, 'porcentaje' => 50]);
        $ev2 = Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => null, 'porcentaje' => 50]);

        $response = $this->postJson("/api/v1/registro-calificaciones/{$asignacion->id_asignacion}/guardar", [
            'notas' => [
                ['id_inscripcion' => $inscripcion->id_inscripcion, 'id_evaluacion' => $ev1->id_evaluacion, 'nota' => 40],
                ['id_inscripcion' => $inscripcion->id_inscripcion, 'id_evaluacion' => $ev2->id_evaluacion, 'nota' => 30],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('detalle_calificacion', [
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'id_evaluacion' => $ev1->id_evaluacion,
            'nota' => 40,
        ]);
        // (40 + 30) * 100 / (50 + 50) = 70
        $notaFinal = (float) $inscripcion->calificacionesFinales()->first()->nota_final;
        $this->assertEquals(70.0, $notaFinal);
    }

    public function test_guardar_notas_respeta_el_tope_de_zona_al_recalcular(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);
        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        $evaluacion = Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona]);

        // La nota excede los puntos de la zona (35 > 30): debe topearse.
        $response = $this->postJson("/api/v1/registro-calificaciones/{$asignacion->id_asignacion}/guardar", [
            'notas' => [
                ['id_inscripcion' => $inscripcion->id_inscripcion, 'id_evaluacion' => $evaluacion->id_evaluacion, 'nota' => 35],
            ],
        ]);

        $response->assertStatus(200);
        $notaFinal = (float) $inscripcion->calificacionesFinales()->first()->nota_final;
        // min(35,30) = 30 de 30 pts de la unica zona -> 100
        $this->assertEquals(100.0, $notaFinal);
    }

    public function test_guardar_una_nota_ya_existente_la_actualiza_sin_duplicar(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);
        $evaluacion = Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => null, 'porcentaje' => 100]);
        DetalleCalificacion::factory()->create([
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'id_evaluacion' => $evaluacion->id_evaluacion,
            'nota' => 50,
        ]);

        $response = $this->postJson("/api/v1/registro-calificaciones/{$asignacion->id_asignacion}/guardar", [
            'notas' => [
                ['id_inscripcion' => $inscripcion->id_inscripcion, 'id_evaluacion' => $evaluacion->id_evaluacion, 'nota' => 90],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(
            1,
            DetalleCalificacion::where('id_inscripcion', $inscripcion->id_inscripcion)
                ->where('id_evaluacion', $evaluacion->id_evaluacion)
                ->count()
        );
        $this->assertDatabaseHas('detalle_calificacion', [
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'id_evaluacion' => $evaluacion->id_evaluacion,
            'nota' => 90,
        ]);
    }

    public function test_rechaza_guardar_notas_si_el_periodo_esta_cerrado(): void
    {
        $periodo = PeriodoAcademico::factory()->cerrado()->create();
        $asignacion = Asignacion::factory()->create(['id_periodo' => $periodo->id_periodo]);
        $this->actuarComoCatedraticoDe($asignacion);
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);
        $evaluacion = Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => null, 'porcentaje' => 100]);

        $response = $this->postJson("/api/v1/registro-calificaciones/{$asignacion->id_asignacion}/guardar", [
            'notas' => [
                ['id_inscripcion' => $inscripcion->id_inscripcion, 'id_evaluacion' => $evaluacion->id_evaluacion, 'nota' => 90],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('detalle_calificacion', ['id_inscripcion' => $inscripcion->id_inscripcion]);
    }
}
