<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\CalificacionFinal;
use App\Models\DetalleCalificacion;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use App\Models\ZonaEvaluacion;
use App\Services\CalificacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalificacionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_nota_final_con_zonas_topea_cada_zona_y_escala_a_100(): void
    {
        $asignacion = Asignacion::factory()->create();
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $zona1 = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        $zona2 = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 70]);

        $ev1 = Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona1->id_zona]);
        $ev2 = Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona2->id_zona]);

        // La nota de la zona 1 excede sus puntos (35 > 30): debe topearse a 30.
        DetalleCalificacion::factory()->create([
            'id_evaluacion' => $ev1->id_evaluacion,
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'nota' => 35,
        ]);
        DetalleCalificacion::factory()->create([
            'id_evaluacion' => $ev2->id_evaluacion,
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'nota' => 50,
        ]);

        CalificacionService::recalcularNotasFinales($asignacion);

        $final = CalificacionFinal::where('id_inscripcion', $inscripcion->id_inscripcion)->first();

        // min(35,30) + min(50,70) = 80; total de puntos de zonas = 100 -> 80 * 100 / 100 = 80
        $this->assertEquals(80.0, (float) $final->nota_final);
    }

    public function test_nota_final_sin_zonas_usa_promedio_ponderado_por_porcentaje(): void
    {
        $asignacion = Asignacion::factory()->create();
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $ev1 = Evaluacion::factory()->create([
            'id_asignacion' => $asignacion->id_asignacion,
            'id_zona' => null,
            'porcentaje' => 50,
        ]);
        $ev2 = Evaluacion::factory()->create([
            'id_asignacion' => $asignacion->id_asignacion,
            'id_zona' => null,
            'porcentaje' => 50,
        ]);

        DetalleCalificacion::factory()->create([
            'id_evaluacion' => $ev1->id_evaluacion,
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'nota' => 40,
        ]);
        DetalleCalificacion::factory()->create([
            'id_evaluacion' => $ev2->id_evaluacion,
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'nota' => 30,
        ]);

        CalificacionService::recalcularNotasFinales($asignacion);

        $final = CalificacionFinal::where('id_inscripcion', $inscripcion->id_inscripcion)->first();

        // (40 + 30) * 100 / (50 + 50) = 70
        $this->assertEquals(70.0, (float) $final->nota_final);
    }

    public function test_evaluaciones_sin_zona_no_cuentan_si_existen_zonas(): void
    {
        $asignacion = Asignacion::factory()->create();
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 100]);
        $evZona = Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona]);
        $evSinZona = Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => null]);

        DetalleCalificacion::factory()->create([
            'id_evaluacion' => $evZona->id_evaluacion,
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'nota' => 60,
        ]);
        // Esta nota no debe influir en el resultado: su evaluación no pertenece a ninguna zona.
        DetalleCalificacion::factory()->create([
            'id_evaluacion' => $evSinZona->id_evaluacion,
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'nota' => 100,
        ]);

        CalificacionService::recalcularNotasFinales($asignacion);

        $final = CalificacionFinal::where('id_inscripcion', $inscripcion->id_inscripcion)->first();

        $this->assertEquals(60.0, (float) $final->nota_final);
    }
}
