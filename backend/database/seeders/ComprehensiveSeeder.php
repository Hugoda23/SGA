<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Configuracion;
use App\Models\Edificio;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\PeriodoAcademico;
use App\Models\Catedratico;
use App\Models\Curso;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\Alumno;
use App\Models\Pensum;
use App\Models\Asignacion;
use App\Models\HorarioDetalle;
use App\Models\Inscripcion;
use App\Models\Asistencia;
use App\Models\Unidad;
use App\Models\ZonaEvaluacion;
use App\Models\Evaluacion;
use App\Models\DetalleCalificacion;
use App\Models\Tarea;
use App\Models\EntregaTarea;
use App\Models\Material;
use App\Models\Anuncio;
use App\Models\Archivo;
use App\Models\Notificacion;
use App\Models\Bitacora;
use App\Models\ReporteGenerado;
use App\Services\CalificacionService;
use Carbon\Carbon;

class ComprehensiveSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');
        
        DB::transaction(function () use ($faker) {
            
            // 1. Configuracion
            Configuracion::firstOrCreate(['clave' => 'nombre_institucion'], ['valor' => 'Instituto Florencio Carrascoza']);
            Configuracion::firstOrCreate(['clave' => 'nota_minima'], ['valor' => '61']);

            // 2. Roles
            $rolAdmin = Rol::where('nombre', 'admin')->firstOrFail();
            $rolAlumno = Rol::where('nombre', 'alumno')->firstOrFail();
            $rolCatedratico = Rol::where('nombre', 'catedratico')->firstOrFail();

            // 3. Admin principal (asegurar)
            $admin = Usuario::firstOrCreate(
                ['username' => 'admin'],
                ['password' => bcrypt('admin123'), 'estado' => 'activo', 'password_change_required' => false]
            );
            if (!$admin->roles()->where('rol.id_rol', $rolAdmin->id_rol)->exists()) {
                $admin->roles()->attach($rolAdmin->id_rol);
            }

            // 4. Edificios y Aulas
            $edificios = [];
            for ($i = 1; $i <= 3; $i++) {
                $edificios[] = Edificio::create([
                    'nombre' => 'Edificio ' . $faker->randomLetter(),
                    'ubicacion' => $faker->streetAddress
                ]);
            }

            $aulas = [];
            foreach ($edificios as $ed) {
                for ($i = 1; $i <= 4; $i++) {
                    $aulas[] = Aula::create([
                        'nombre_aula' => 'Aula ' . $faker->numberBetween(100, 400),
                        'capacidad' => $faker->numberBetween(20, 50),
                        'id_edificio' => $ed->id_edificio
                    ]);
                }
            }

            // 5. Carreras
            $nombresCarreras = ['Ingeniería en Sistemas', 'Arquitectura', 'Derecho', 'Medicina', 'Administración de Empresas'];
            $carreras = [];
            foreach ($nombresCarreras as $nom) {
                $carreras[] = Carrera::create([
                    'nombre_carrera' => $nom,
                    'descripcion' => $faker->sentence
                ]);
            }

            // 5b. Grados y Secciones
            $grados = [];
            foreach ([
                ['nombre' => 'Primero Básico', 'nivel' => 'Básico'],
                ['nombre' => 'Segundo Básico', 'nivel' => 'Básico'],
                ['nombre' => 'Tercero Básico', 'nivel' => 'Básico'],
                ['nombre' => 'Cuarto Diversificado', 'nivel' => 'Diversificado'],
                ['nombre' => 'Quinto Diversificado', 'nivel' => 'Diversificado'],
            ] as $g) {
                $grados[] = Grado::create($g);
            }

            $secciones = [];
            foreach (['A', 'B', 'C'] as $nombreSeccion) {
                $secciones[] = Seccion::create(['nombre' => $nombreSeccion]);
            }

            // 6. Periodo Académico
            $periodos = [];
            $periodos[] = PeriodoAcademico::create([
                'nombre' => 'Semestre I 2026', 'fecha_inicio' => '2026-01-15', 'fecha_fin' => '2026-06-15', 'estado' => 'inactivo'
            ]);
            $periodos[] = PeriodoAcademico::create([
                'nombre' => 'Semestre II 2026', 'fecha_inicio' => '2026-07-15', 'fecha_fin' => '2026-12-15', 'estado' => 'activo'
            ]);

            // 7. Catedráticos
            $catedraticos = [];
            for ($i = 1; $i <= 15; $i++) {
                $user = Usuario::create([
                    'username' => 'CAT' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'password' => bcrypt('password'),
                    'estado' => 'activo'
                ]);
                $user->roles()->attach($rolCatedratico->id_rol);

                $catedraticos[] = Catedratico::create([
                    'id_usuario' => $user->id_usuario,
                    'codigo' => 'CAT' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'nombre' => $faker->firstName,
                    'apellido' => $faker->lastName,
                    'especialidad' => $faker->jobTitle,
                    'correo' => $faker->unique()->safeEmail,
                    'telefono' => $faker->numerify('########')
                ]);
            }

            // 8. Cursos
            $nombresCursos = ['Matemáticas I', 'Programación I', 'Física I', 'Historia', 'Contabilidad', 'Derecho Civil', 'Anatomía', 'Estadística', 'Química', 'Filosofía'];
            $cursos = [];
            foreach ($nombresCursos as $nom) {
                $curso = Curso::create([
                    'nombre_curso' => $nom,
                    'descripcion' => $faker->sentence,
                ]);
                $carrerasCurso = $faker->randomElements($carreras, $faker->numberBetween(1, 3));
                $curso->carreras()->sync(array_map(fn ($c) => $c->id_carrera, $carrerasCurso));
                $cursos[] = $curso;
            }

            // 9. Alumnos
            $alumnos = [];
            for ($i = 1; $i <= 50; $i++) {
                $user = Usuario::create([
                    'username' => 'ALU' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'password' => bcrypt('password'),
                    'estado' => 'activo'
                ]);
                $user->roles()->attach($rolAlumno->id_rol);

                $alumnos[] = Alumno::create([
                    'id_usuario' => $user->id_usuario,
                    'nombre' => $faker->firstName,
                    'apellido' => $faker->lastName,
                    'codigo_mineduc' => 'ALU' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'correo' => $faker->unique()->safeEmail,
                    'telefono' => $faker->numerify('########'),
                    'fecha_nacimiento' => $faker->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),
                    'nacionalidad' => 'Guatemalteca',
                    'tipo_documento' => 'cui',
                    'numero_documento' => '2' . str_pad($i, 12, '0', STR_PAD_LEFT),
                    'id_carrera' => $faker->randomElement($carreras)->id_carrera,
                    'id_grado_actual' => $faker->randomElement($grados)->id_grado,
                    'estado_academico' => 'activo'
                ]);
            }

            // 10. Pensum
            foreach ($cursos as $curso) {
                $carrera = $faker->randomElement($carreras);
                Pensum::create([
                    'id_carrera' => $carrera->id_carrera,
                    'id_curso' => $curso->id_curso,
                    'id_grado' => Grado::inRandomOrder()->value('id_grado'),
                    'obligatorio' => $faker->boolean(80)
                ]);
            }

            // 11. Asignaciones (Cursos a Catedráticos)
            $asignaciones = [];
            for ($i = 0; $i < 20; $i++) {
                $asignaciones[] = Asignacion::create([
                    'id_catedratico' => $faker->randomElement($catedraticos)->id_catedratico,
                    'id_curso' => $faker->randomElement($cursos)->id_curso,
                    'id_aula' => $faker->randomElement($aulas)->id_aula,
                    'id_periodo' => $periodos[1]->id_periodo, // Activo
                    'id_grado' => $faker->randomElement($grados)->id_grado,
                    'id_seccion' => $faker->randomElement($secciones)->id_seccion,
                ]);
            }

            // 12. HorarioDetalle
            foreach ($asignaciones as $asig) {
                HorarioDetalle::create([
                    'id_asignacion' => $asig->id_asignacion,
                    'dia_semana' => $faker->randomElement(['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes']),
                    'hora_inicio' => '08:00',
                    'hora_fin' => '10:00'
                ]);
            }

            // 13. Inscripciones
            $inscripciones = [];
            foreach ($alumnos as $alumno) {
                // Inscribir a 2 asignaciones aleatorias
                $asigRandoms = $faker->randomElements($asignaciones, 2);
                foreach ($asigRandoms as $asig) {
                    $inscripciones[] = Inscripcion::create([
                        'id_alumno' => $alumno->id_alumno,
                        'id_asignacion' => $asig->id_asignacion,
                        'fecha_inscripcion' => Carbon::now()->subDays($faker->numberBetween(10, 30))
                    ]);
                }
            }

            // 14. Asistencias
            foreach ($inscripciones as $inscripcion) {
                for ($day = 0; $day < 3; $day++) {
                    Asistencia::create([
                        'id_inscripcion' => $inscripcion->id_inscripcion,
                        'fecha' => Carbon::now()->subDays($day),
                        'estado' => $faker->randomElement(['presente', 'ausente', 'justificado'])
                    ]);
                }
            }

            // 15. Unidades (avance programático), Zonas de evaluación, Evaluaciones,
            // Tareas y Materiales — todo vinculado a cada asignación.
            $evaluacionesPorAsignacion = [];
            $tareas = [];
            $temasPorSemana = [
                ['titulo' => 'Introducción y fundamentos', 'temas' => "Presentación del curso\nConceptos básicos", 'competencia' => 'Identifica los conceptos fundamentales de la materia.', 'estado' => 'completado'],
                ['titulo' => 'Desarrollo de contenidos', 'temas' => "Profundización teórica\nCasos prácticos", 'competencia' => 'Aplica los conceptos aprendidos en ejercicios prácticos.', 'estado' => 'en_progreso'],
                ['titulo' => 'Cierre y evaluación', 'temas' => "Repaso general\nEvaluación final de unidad", 'competencia' => 'Integra y demuestra el dominio de los temas del curso.', 'estado' => 'planificado'],
            ];

            foreach ($asignaciones as $asig) {
                // Unidades del avance programático
                $unidadesAsig = [];
                foreach ($temasPorSemana as $semana => $def) {
                    $unidadesAsig[] = Unidad::create([
                        'id_asignacion' => $asig->id_asignacion,
                        'numero_semana' => $semana + 1,
                        'titulo' => $def['titulo'],
                        'temas' => $def['temas'],
                        'competencia' => $def['competencia'],
                        'estado' => $def['estado'],
                        'fecha_inicio' => Carbon::now()->subWeeks(3 - $semana),
                        'fecha_fin' => Carbon::now()->subWeeks(3 - $semana)->addDays(6),
                    ]);
                }

                // Zonas de evaluación (100 puntos repartidos entre las 3) con
                // una actividad evaluable por zona.
                $zonaDefs = [
                    ['nombre' => 'Zona 1', 'puntos' => 30],
                    ['nombre' => 'Zona 2', 'puntos' => 30],
                    ['nombre' => 'Zona 3', 'puntos' => 40],
                ];
                $evalsAsig = [];
                foreach ($zonaDefs as $pos => $def) {
                    $zona = ZonaEvaluacion::create([
                        'id_asignacion' => $asig->id_asignacion,
                        'nombre' => $def['nombre'],
                        'puntos' => $def['puntos'],
                        'posicion' => $pos,
                    ]);

                    $evalsAsig[] = Evaluacion::create([
                        'id_asignacion' => $asig->id_asignacion,
                        'id_zona' => $zona->id_zona,
                        'unidad_academica' => 1,
                        'nombre' => $def['nombre'] . ' - Actividades',
                        'porcentaje' => $def['puntos'],
                    ]);
                }
                $evaluacionesPorAsignacion[$asig->id_asignacion] = $evalsAsig;

                // Tarea vinculada a la primera unidad
                $tareas[] = Tarea::create([
                    'titulo' => 'Ensayo ' . $faker->word,
                    'descripcion' => $faker->sentence,
                    'puntos' => $faker->randomElement([10, 15, 20, 25]),
                    'fecha_entrega' => Carbon::now()->addDays($faker->numberBetween(1, 10)),
                    'id_asignacion' => $asig->id_asignacion,
                    'id_unidad' => $unidadesAsig[0]->id_unidad,
                ]);

                // Material: un archivo descargable y un enlace externo
                $archivo = Archivo::create([
                    'nombre' => 'Guia_' . $asig->id_asignacion . '.pdf',
                    'ruta' => 'materiales/guia-' . $asig->id_asignacion . '.pdf',
                    'tipo' => 'application/pdf',
                    'fecha_subida' => Carbon::now()->subDays($faker->numberBetween(1, 15)),
                ]);
                Material::create([
                    'id_asignacion' => $asig->id_asignacion,
                    'id_unidad' => $unidadesAsig[0]->id_unidad,
                    'titulo' => 'Guía de estudio - Unidad 1',
                    'descripcion' => $faker->sentence,
                    'tipo' => 'archivo',
                    'id_archivo' => $archivo->id_archivo,
                    'fecha_publicacion' => Carbon::now()->subDays($faker->numberBetween(1, 15)),
                ]);
                Material::create([
                    'id_asignacion' => $asig->id_asignacion,
                    'id_unidad' => $unidadesAsig[1]->id_unidad,
                    'titulo' => 'Video de apoyo',
                    'descripcion' => $faker->sentence,
                    'tipo' => 'enlace',
                    'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'fecha_publicacion' => Carbon::now()->subDays($faker->numberBetween(1, 10)),
                ]);

                // Anuncio de bienvenida del curso
                Anuncio::create([
                    'id_asignacion' => $asig->id_asignacion,
                    'titulo' => 'Bienvenida al curso',
                    'contenido' => $faker->paragraph,
                    'fecha_publicacion' => Carbon::now()->subDays($faker->numberBetween(1, 20)),
                ]);
            }

            // 16. DetalleCalificacion: una nota por cada actividad de zona,
            // y luego se recalcula la nota final con CalificacionService —
            // el mismo servicio real que usa la app — en vez de un valor
            // aleatorio desconectado, para que la data sembrada sea
            // consistente end-to-end.
            foreach ($inscripciones as $insc) {
                foreach ($evaluacionesPorAsignacion[$insc->id_asignacion] ?? [] as $ev) {
                    DetalleCalificacion::create([
                        'id_evaluacion' => $ev->id_evaluacion,
                        'id_inscripcion' => $insc->id_inscripcion,
                        'nota' => $faker->randomFloat(2, 0, (float) $ev->porcentaje),
                    ]);
                }
            }

            foreach ($asignaciones as $asig) {
                CalificacionService::recalcularNotasFinales($asig);
            }

            // 17. EntregaTarea y Archivos
            foreach ($alumnos as $alumno) {
                // Just create random entregas
                if ($faker->boolean(50)) {
                    $tarea = $faker->randomElement($tareas);
                    $entrega = EntregaTarea::create([
                        'id_tarea' => $tarea->id_tarea,
                        'id_alumno' => $alumno->id_alumno,
                        'archivo' => 'storage/tareas/archivo_' . $alumno->id_alumno . '.pdf',
                        'fecha_entrega' => Carbon::now(),
                        'estado' => 'entregada',
                        'calificacion' => $faker->randomFloat(2, 0, (float) ($tarea->puntos ?? 100)),
                    ]);

                }
            }

            // 18. Notificaciones y Bitacora
            for ($i = 0; $i < 20; $i++) {
                Notificacion::create([
                    'id_usuario' => $faker->randomElement($alumnos)->id_usuario,
                    'mensaje' => 'Recordatorio de clase ' . $faker->word,
                    'fecha' => Carbon::now(),
                    'leido' => $faker->boolean
                ]);

                Bitacora::create([
                    'id_usuario' => $admin->id_usuario,
                    'accion' => 'CREATE',
                    'tabla_afectada' => 'usuarios',
                    'descripcion' => 'Creación masiva de datos',
                    'fecha_hora' => Carbon::now()
                ]);
            }

            // 19. ReporteGenerado
            ReporteGenerado::create([
                'id_usuario' => $admin->id_usuario,
                'tipo_reporte' => 'consolidado_notas',
                'fecha_generacion' => Carbon::now(),
                'tiempo_generacion' => 1.50
            ]);
            
        });
    }
}
