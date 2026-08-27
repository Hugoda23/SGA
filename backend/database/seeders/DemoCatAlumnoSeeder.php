<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\DetalleCalificacion;
use App\Models\EntregaTarea;
use App\Models\Evaluacion;
use App\Models\HorarioDetalle;
use App\Models\Inscripcion;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\Tarea;
use App\Models\Unidad;
use App\Models\Usuario;
use App\Models\ZonaEvaluacion;
use App\Services\CalificacionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Asegura que CAT001 dicte un curso en el que ALU0001 está inscrito, con
 * horario, unidades, zonas de evaluación, notas y una tarea entregada —
 * para poder probar el flujo catedrático↔alumno de punta a punta con
 * estas dos cuentas de prueba. Idempotente: se puede correr varias veces
 * (php artisan db:seed --class=DemoCatAlumnoSeeder) sin duplicar datos.
 * Requiere que CAT001 y ALU0001 ya existan (ComprehensiveSeeder).
 */
class DemoCatAlumnoSeeder extends Seeder
{
    public function run(): void
    {
        $catedratico = Usuario::where('username', 'CAT001')->first()?->catedratico;
        $alumno = Usuario::where('username', 'ALU0001')->first()?->alumno;

        if (!$catedratico || !$alumno) {
            $this->command?->error('Faltan CAT001 y/o ALU0001 — corré primero php artisan db:seed.');
            return;
        }

        $periodo = PeriodoAcademico::where('estado', 'activo')->first() ?? PeriodoAcademico::firstOrFail();
        $curso = Curso::firstOrCreate(
            ['nombre_curso' => 'Programación I'],
            ['descripcion' => 'Fundamentos de programación estructurada.']
        );
        $aula = Aula::firstOrFail();
        $seccion = Seccion::firstOrFail();

        $asignacion = Asignacion::firstOrCreate(
            [
                'id_catedratico' => $catedratico->id_catedratico,
                'id_curso' => $curso->id_curso,
                'id_periodo' => $periodo->id_periodo,
            ],
            [
                'id_aula' => $aula->id_aula,
                'id_grado' => $alumno->id_grado_actual,
                'id_seccion' => $seccion->id_seccion,
            ]
        );

        HorarioDetalle::firstOrCreate(
            ['id_asignacion' => $asignacion->id_asignacion, 'dia_semana' => 'Lunes'],
            ['hora_inicio' => '08:00', 'hora_fin' => '10:00']
        );

        $inscripcion = Inscripcion::firstOrCreate(
            ['id_alumno' => $alumno->id_alumno, 'id_asignacion' => $asignacion->id_asignacion, 'estado' => 'activo'],
            ['fecha_inscripcion' => Carbon::now()->subDays(15)]
        );

        $unidad = Unidad::firstOrCreate(
            ['id_asignacion' => $asignacion->id_asignacion, 'numero_semana' => 1],
            [
                'titulo' => 'Introducción y fundamentos',
                'temas' => "Presentación del curso\nConceptos básicos",
                'competencia' => 'Identifica los conceptos fundamentales de la materia.',
                'estado' => 'en_progreso',
                'fecha_inicio' => Carbon::now()->subWeeks(2),
                'fecha_fin' => Carbon::now()->subWeeks(2)->addDays(6),
            ]
        );

        $zonaDefs = [
            ['nombre' => 'Zona 1', 'puntos' => 30, 'posicion' => 0],
            ['nombre' => 'Zona 2', 'puntos' => 30, 'posicion' => 1],
            ['nombre' => 'Zona 3', 'puntos' => 40, 'posicion' => 2],
        ];
        $zonaUno = null;
        foreach ($zonaDefs as $def) {
            $zona = ZonaEvaluacion::firstOrCreate(
                ['id_asignacion' => $asignacion->id_asignacion, 'nombre' => $def['nombre']],
                ['puntos' => $def['puntos'], 'posicion' => $def['posicion']]
            );
            $zonaUno ??= $zona;

            $evaluacion = Evaluacion::firstOrCreate(
                ['id_asignacion' => $asignacion->id_asignacion, 'id_zona' => $zona->id_zona],
                [
                    'unidad_academica' => 1,
                    'nombre' => $def['nombre'] . ' - Actividades',
                    'porcentaje' => $def['puntos'],
                ]
            );

            DetalleCalificacion::firstOrCreate(
                ['id_evaluacion' => $evaluacion->id_evaluacion, 'id_inscripcion' => $inscripcion->id_inscripcion],
                ['nota' => round($def['puntos'] * 0.8, 2)]
            );
        }

        $tarea = Tarea::firstOrCreate(
            ['id_asignacion' => $asignacion->id_asignacion, 'titulo' => 'Ensayo: Introducción a la programación'],
            [
                'descripcion' => 'Redactar un ensayo corto sobre los fundamentos vistos en clase.',
                'puntos' => 20,
                'id_unidad' => $unidad->id_unidad,
                'id_zona' => $zonaUno?->id_zona,
                'fecha_entrega' => Carbon::now()->addDays(7),
            ]
        );

        EntregaTarea::firstOrCreate(
            ['id_tarea' => $tarea->id_tarea, 'id_alumno' => $alumno->id_alumno],
            [
                'link' => 'https://docs.google.com/document/d/demo-ensayo-alu0001',
                'fecha_entrega' => Carbon::now()->subDays(1),
                'estado' => 'entregada',
                'calificacion' => 18,
            ]
        );

        CalificacionService::recalcularNotasFinales($asignacion);

        $this->command?->info(
            "Listo: \"{$curso->nombre_curso}\" — CAT001 dicta, ALU0001 inscrito (asignación #{$asignacion->id_asignacion})."
        );
    }
}
