<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\HorarioDetalle;
use App\Models\Inscripcion;
use App\Models\PeriodoAcademico;
use App\Services\InscripcionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InscripcionServiceTest extends TestCase
{
    use RefreshDatabase;

    private InscripcionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InscripcionService();
    }

    public function test_permite_inscripcion_cuando_no_hay_conflictos(): void
    {
        $aula = Aula::factory()->create(['capacidad' => 30]);
        $periodo = PeriodoAcademico::factory()->create(['estado' => 'activo']);
        $carrera = Carrera::factory()->create();
        $curso = Curso::factory()->create();
        $curso->carreras()->attach($carrera->id_carrera);
        $asignacion = \App\Models\Asignacion::factory()->create([
            'id_aula' => $aula->id_aula,
            'id_periodo' => $periodo->id_periodo,
            'id_curso' => $curso->id_curso,
        ]);
        $alumno = Alumno::factory()->create(['id_carrera' => $carrera->id_carrera]);

        $errores = $this->service->verificarReglas($alumno->id_alumno, $asignacion->id_asignacion);

        $this->assertSame([], $errores);
    }

    public function test_rechaza_inscripcion_en_periodo_cerrado(): void
    {
        $periodo = PeriodoAcademico::factory()->create(['estado' => 'cerrado']);
        $asignacion = \App\Models\Asignacion::factory()->create(['id_periodo' => $periodo->id_periodo]);
        $alumno = Alumno::factory()->create(['id_carrera' => null]);

        $errores = $this->service->verificarReglas($alumno->id_alumno, $asignacion->id_asignacion);

        $this->assertContains('No se puede inscribir: el periodo académico está cerrado.', $errores);
    }

    public function test_rechaza_inscripcion_activa_duplicada(): void
    {
        $asignacion = \App\Models\Asignacion::factory()->create();
        $alumno = Alumno::factory()->create(['id_carrera' => null]);

        Inscripcion::factory()->create([
            'id_alumno' => $alumno->id_alumno,
            'id_asignacion' => $asignacion->id_asignacion,
            'estado' => 'activo',
        ]);

        $errores = $this->service->verificarReglas($alumno->id_alumno, $asignacion->id_asignacion);

        $this->assertContains('El alumno ya está inscrito en esta asignación.', $errores);
    }

    public function test_permite_reinscripcion_tras_retiro(): void
    {
        $asignacion = \App\Models\Asignacion::factory()->create();
        $alumno = Alumno::factory()->create(['id_carrera' => null]);

        Inscripcion::factory()->retirado()->create([
            'id_alumno' => $alumno->id_alumno,
            'id_asignacion' => $asignacion->id_asignacion,
        ]);

        $errores = $this->service->verificarReglas($alumno->id_alumno, $asignacion->id_asignacion);

        $this->assertSame([], $errores);
    }

    public function test_rechaza_inscripcion_cuando_el_aula_alcanzo_su_cupo(): void
    {
        $aula = Aula::factory()->create(['capacidad' => 1]);
        $asignacion = \App\Models\Asignacion::factory()->create(['id_aula' => $aula->id_aula]);

        Inscripcion::factory()->create([
            'id_asignacion' => $asignacion->id_asignacion,
            'estado' => 'activo',
        ]);

        $nuevoAlumno = Alumno::factory()->create(['id_carrera' => null]);

        $errores = $this->service->verificarReglas($nuevoAlumno->id_alumno, $asignacion->id_asignacion);

        $this->assertContains('El aula ha alcanzado su cupo máximo de 1 alumnos.', $errores);
    }

    public function test_rechaza_inscripcion_cuando_la_carrera_no_corresponde_al_curso(): void
    {
        $carreraCurso = Carrera::factory()->create();
        $otraCarrera = Carrera::factory()->create();

        $curso = Curso::factory()->create();
        $curso->carreras()->attach($carreraCurso->id_carrera);

        $asignacion = \App\Models\Asignacion::factory()->create(['id_curso' => $curso->id_curso]);
        $alumno = Alumno::factory()->create(['id_carrera' => $otraCarrera->id_carrera]);

        $errores = $this->service->verificarReglas($alumno->id_alumno, $asignacion->id_asignacion);

        $this->assertContains('La carrera del alumno no corresponde con el curso de la asignación.', $errores);
    }

    public function test_rechaza_inscripcion_por_choque_de_horario(): void
    {
        $periodo = PeriodoAcademico::factory()->create();
        $alumno = Alumno::factory()->create(['id_carrera' => null]);

        $asignacionExistente = \App\Models\Asignacion::factory()->create(['id_periodo' => $periodo->id_periodo]);
        HorarioDetalle::factory()->create([
            'id_asignacion' => $asignacionExistente->id_asignacion,
            'dia_semana' => 'lunes',
            'hora_inicio' => '08:00:00',
            'hora_fin' => '10:00:00',
        ]);
        Inscripcion::factory()->create([
            'id_alumno' => $alumno->id_alumno,
            'id_asignacion' => $asignacionExistente->id_asignacion,
            'estado' => 'activo',
        ]);

        $asignacionNueva = \App\Models\Asignacion::factory()->create(['id_periodo' => $periodo->id_periodo]);
        HorarioDetalle::factory()->create([
            'id_asignacion' => $asignacionNueva->id_asignacion,
            'dia_semana' => 'lunes',
            'hora_inicio' => '09:00:00',
            'hora_fin' => '11:00:00',
        ]);

        $errores = $this->service->verificarReglas($alumno->id_alumno, $asignacionNueva->id_asignacion);

        $this->assertContains('La asignación se cruza con el horario de otra asignación del alumno.', $errores);
    }

    public function test_permite_horarios_del_mismo_dia_que_no_se_solapan(): void
    {
        $periodo = PeriodoAcademico::factory()->create();
        $alumno = Alumno::factory()->create(['id_carrera' => null]);

        $asignacionExistente = \App\Models\Asignacion::factory()->create(['id_periodo' => $periodo->id_periodo]);
        HorarioDetalle::factory()->create([
            'id_asignacion' => $asignacionExistente->id_asignacion,
            'dia_semana' => 'lunes',
            'hora_inicio' => '08:00:00',
            'hora_fin' => '09:00:00',
        ]);
        Inscripcion::factory()->create([
            'id_alumno' => $alumno->id_alumno,
            'id_asignacion' => $asignacionExistente->id_asignacion,
            'estado' => 'activo',
        ]);

        $asignacionNueva = \App\Models\Asignacion::factory()->create(['id_periodo' => $periodo->id_periodo]);
        HorarioDetalle::factory()->create([
            'id_asignacion' => $asignacionNueva->id_asignacion,
            'dia_semana' => 'lunes',
            'hora_inicio' => '09:00:00',
            'hora_fin' => '10:00:00',
        ]);

        $errores = $this->service->verificarReglas($alumno->id_alumno, $asignacionNueva->id_asignacion);

        $this->assertSame([], $errores);
    }
}
