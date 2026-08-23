<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\HorarioDetalle;
use App\Models\PeriodoAcademico;
use App\Services\HorarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioServiceTest extends TestCase
{
    use RefreshDatabase;

    private HorarioService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HorarioService();
    }

    public function test_permite_horario_cuando_el_aula_esta_libre(): void
    {
        $aula = Aula::factory()->create();
        $periodo = PeriodoAcademico::factory()->create();
        $asignacion = \App\Models\Asignacion::factory()->create(['id_aula' => $aula->id_aula, 'id_periodo' => $periodo->id_periodo]);

        $errores = $this->service->verificarChoqueAula(
            $asignacion->id_asignacion,
            $aula->id_aula,
            $periodo->id_periodo,
            'lunes',
            '08:00:00',
            '09:00:00'
        );

        $this->assertSame([], $errores);
    }

    public function test_rechaza_dos_asignaciones_distintas_en_la_misma_aula_con_horario_solapado(): void
    {
        $aula = Aula::factory()->create();
        $periodo = PeriodoAcademico::factory()->create();

        $asignacionExistente = \App\Models\Asignacion::factory()->create(['id_aula' => $aula->id_aula, 'id_periodo' => $periodo->id_periodo]);
        HorarioDetalle::factory()->create([
            'id_asignacion' => $asignacionExistente->id_asignacion,
            'dia_semana' => 'martes',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '12:00:00',
        ]);

        $asignacionNueva = \App\Models\Asignacion::factory()->create(['id_aula' => $aula->id_aula, 'id_periodo' => $periodo->id_periodo]);

        $errores = $this->service->verificarChoqueAula(
            $asignacionNueva->id_asignacion,
            $aula->id_aula,
            $periodo->id_periodo,
            'martes',
            '11:00:00',
            '13:00:00'
        );

        $this->assertNotEmpty($errores);
        $this->assertStringContainsString('El aula ya está reservada', $errores[0]);
    }

    public function test_permite_la_misma_aula_en_periodos_distintos(): void
    {
        $aula = Aula::factory()->create();
        $periodoA = PeriodoAcademico::factory()->create();
        $periodoB = PeriodoAcademico::factory()->create();

        $asignacionExistente = \App\Models\Asignacion::factory()->create(['id_aula' => $aula->id_aula, 'id_periodo' => $periodoA->id_periodo]);
        HorarioDetalle::factory()->create([
            'id_asignacion' => $asignacionExistente->id_asignacion,
            'dia_semana' => 'lunes',
            'hora_inicio' => '08:00:00',
            'hora_fin' => '09:00:00',
        ]);

        $asignacionNueva = \App\Models\Asignacion::factory()->create(['id_aula' => $aula->id_aula, 'id_periodo' => $periodoB->id_periodo]);

        $errores = $this->service->verificarChoqueAula(
            $asignacionNueva->id_asignacion,
            $aula->id_aula,
            $periodoB->id_periodo,
            'lunes',
            '08:00:00',
            '09:00:00'
        );

        $this->assertSame([], $errores);
    }

    public function test_permite_actualizar_un_horario_sin_chocar_consigo_mismo(): void
    {
        $aula = Aula::factory()->create();
        $periodo = PeriodoAcademico::factory()->create();
        $asignacion = \App\Models\Asignacion::factory()->create(['id_aula' => $aula->id_aula, 'id_periodo' => $periodo->id_periodo]);

        $horario = HorarioDetalle::factory()->create([
            'id_asignacion' => $asignacion->id_asignacion,
            'dia_semana' => 'lunes',
            'hora_inicio' => '08:00:00',
            'hora_fin' => '09:00:00',
        ]);

        // Reajustar el mismo horario 15 minutos: no debe chocar consigo mismo.
        $errores = $this->service->verificarChoqueAula(
            $asignacion->id_asignacion,
            $aula->id_aula,
            $periodo->id_periodo,
            'lunes',
            '08:15:00',
            '09:15:00',
            $horario->id_horario
        );

        $this->assertSame([], $errores);
    }
}
