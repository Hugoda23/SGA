<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\CalificacionFinal;
use App\Models\DetalleCalificacion;
use App\Models\EntregaTarea;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use App\Models\Tarea;
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

    public function test_las_tareas_vinculadas_a_una_zona_cuentan_para_la_nota_final(): void
    {
        // Bug arquitectónico corregido: antes CalificacionService solo sumaba
        // Evaluacion/DetalleCalificacion — una Tarea podía reservar puntos de
        // una zona pero su calificación (EntregaTarea.calificacion) nunca
        // llegaba a la nota final del alumno.
        $asignacion = Asignacion::factory()->create();
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 30]);
        $evaluacion = Evaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona]);
        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'puntos' => 15]);

        DetalleCalificacion::factory()->create([
            'id_evaluacion' => $evaluacion->id_evaluacion,
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'nota' => 10,
        ]);
        EntregaTarea::factory()->create([
            'id_tarea' => $tarea->id_tarea,
            'id_alumno' => $inscripcion->id_alumno,
            'calificacion' => 15,
        ]);

        CalificacionService::recalcularNotasFinales($asignacion);

        $final = CalificacionFinal::where('id_inscripcion', $inscripcion->id_inscripcion)->first();

        // 10 (evaluación) + 15 (tarea) = 25 de 30 pts de la única zona -> 25*100/30
        $this->assertEquals(round(25 * 100 / 30, 2), (float) $final->nota_final);
    }

    public function test_tarea_vinculada_a_zona_se_topea_igual_que_las_evaluaciones(): void
    {
        $asignacion = Asignacion::factory()->create();
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $zona = ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 20]);
        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona, 'puntos' => 20]);

        // La tarea aporta más de lo que la zona permite en total (no debería
        // poder pasar la validación de capacidad al crearla, pero si los
        // datos ya existían, el cálculo igual debe topear correctamente).
        EntregaTarea::factory()->create([
            'id_tarea' => $tarea->id_tarea,
            'id_alumno' => $inscripcion->id_alumno,
            'calificacion' => 20,
        ]);

        CalificacionService::recalcularNotasFinales($asignacion);

        $final = CalificacionFinal::where('id_inscripcion', $inscripcion->id_inscripcion)->first();

        $this->assertEquals(100.0, (float) $final->nota_final);
    }
}
