<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asignacion;
use App\Models\CalificacionFinal;
use App\Models\Grado;
use App\Models\Inscripcion;
use App\Models\PeriodoAcademico;
use App\Services\PromocionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromocionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function inscripcionConNota(PeriodoAcademico $periodo, Alumno $alumno, ?float $notaFinal): Inscripcion
    {
        $asignacion = Asignacion::factory()->create(['id_periodo' => $periodo->id_periodo]);
        $inscripcion = Inscripcion::factory()->create([
            'id_alumno' => $alumno->id_alumno,
            'id_asignacion' => $asignacion->id_asignacion,
        ]);

        if ($notaFinal !== null) {
            CalificacionFinal::factory()->create([
                'id_inscripcion' => $inscripcion->id_inscripcion,
                'nota_final' => $notaFinal,
            ]);
        }

        return $inscripcion;
    }

    public function test_alumno_sin_notas_no_se_promueve(): void
    {
        $periodo = PeriodoAcademico::factory()->create();
        $grado = Grado::factory()->create();
        $alumno = Alumno::factory()->create(['id_grado_actual' => $grado->id_grado, 'id_carrera' => null]);

        $this->inscripcionConNota($periodo, $alumno, null);

        $resultado = (new PromocionService())->promoverPeriodo($periodo->id_periodo);

        $this->assertSame(1, $resultado['resumen']['sin_notas']);
        $this->assertSame('sin_notas', $resultado['detalle'][$alumno->id_alumno]);
        $this->assertSame($grado->id_grado, $alumno->fresh()->id_grado_actual);
    }

    public function test_alumno_reprobado_conserva_su_grado(): void
    {
        $periodo = PeriodoAcademico::factory()->create();
        $gradoActual = Grado::factory()->create();
        Grado::factory()->create(); // grado siguiente, no debería alcanzarlo
        $alumno = Alumno::factory()->create(['id_grado_actual' => $gradoActual->id_grado, 'id_carrera' => null]);

        $this->inscripcionConNota($periodo, $alumno, 50); // por debajo de la nota mínima por defecto (61)

        $resultado = (new PromocionService())->promoverPeriodo($periodo->id_periodo);

        $this->assertSame(1, $resultado['resumen']['reprobados']);
        $this->assertSame('reprobado', $resultado['detalle'][$alumno->id_alumno]);
        $this->assertSame($gradoActual->id_grado, $alumno->fresh()->id_grado_actual);
    }

    public function test_alumno_aprobado_se_promueve_al_siguiente_grado(): void
    {
        $periodo = PeriodoAcademico::factory()->create();
        $gradoActual = Grado::factory()->create();
        $gradoSiguiente = Grado::factory()->create();
        $alumno = Alumno::factory()->create(['id_grado_actual' => $gradoActual->id_grado, 'id_carrera' => null]);

        $this->inscripcionConNota($periodo, $alumno, 75);

        $resultado = (new PromocionService())->promoverPeriodo($periodo->id_periodo);

        $this->assertSame(1, $resultado['resumen']['aprobados']);
        $this->assertSame('promovido', $resultado['detalle'][$alumno->id_alumno]);
        $this->assertSame($gradoSiguiente->id_grado, $alumno->fresh()->id_grado_actual);
    }

    public function test_alumno_aprobado_en_el_ultimo_grado_egresa(): void
    {
        $periodo = PeriodoAcademico::factory()->create();
        $ultimoGrado = Grado::factory()->create();
        $alumno = Alumno::factory()->create([
            'id_grado_actual' => $ultimoGrado->id_grado,
            'id_carrera' => null,
            'estado_academico' => 'activo',
        ]);

        $this->inscripcionConNota($periodo, $alumno, 90);

        $resultado = (new PromocionService())->promoverPeriodo($periodo->id_periodo);

        $this->assertSame(1, $resultado['resumen']['egresados']);
        $this->assertSame('egresado', $resultado['detalle'][$alumno->id_alumno]);
        $this->assertSame('egresado', $alumno->fresh()->estado_academico);
    }
}
