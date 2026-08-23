<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\Catedratico;
use App\Models\CalificacionFinal;
use App\Models\DetalleCalificacion;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Tarea;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug de seguridad encontrado y corregido: TareaController, AsistenciaController,
 * EntregaTareaController, DetalleCalificacionController y CalificacionFinalController
 * exponen sus modelos también por rutas REST genéricas (apiResource), protegidas
 * solo por el permiso general del rol (ej. "entregas.calificar") — sin verificar
 * que el catedrático autenticado sea dueño del curso al que pertenece el recurso.
 * Un catedrático con ese permiso podía editar/borrar notas, asistencia y tareas
 * de un curso ajeno usando el endpoint genérico en vez del endpoint específico
 * (que sí tenía la verificación).
 */
class SeguridadRecursosCalificacionesTest extends TestCase
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

    public function test_no_puede_crear_tarea_en_curso_ajeno_via_endpoint_generico(): void
    {
        $this->actuarComoCatedratico(['tareas.crear']);
        $asignacionAjena = Asignacion::factory()->create();
        $zona = \App\Models\ZonaEvaluacion::factory()->create(['id_asignacion' => $asignacionAjena->id_asignacion, 'puntos' => 30]);

        $response = $this->postJson('/api/v1/tareas', [
            'titulo' => 'Tarea intrusa',
            'puntos' => 10,
            'id_zona' => $zona->id_zona,
            'id_asignacion' => $asignacionAjena->id_asignacion,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('tarea', ['titulo' => 'Tarea intrusa']);
    }

    public function test_no_puede_editar_tarea_de_curso_ajeno_via_endpoint_generico(): void
    {
        $this->actuarComoCatedratico(['tareas.editar']);
        $asignacionAjena = Asignacion::factory()->create();
        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacionAjena->id_asignacion, 'puntos' => 10]);

        $response = $this->putJson("/api/v1/tareas/{$tarea->id_tarea}", ['titulo' => 'Hackeada']);

        $response->assertStatus(403);
        $this->assertNotEquals('Hackeada', $tarea->fresh()->titulo);
    }

    public function test_no_puede_registrar_asistencia_en_curso_ajeno_via_endpoint_generico(): void
    {
        $this->actuarComoCatedratico(['asistencias.registrar']);
        $asignacionAjena = Asignacion::factory()->create();
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacionAjena->id_asignacion]);

        $response = $this->postJson('/api/v1/asistencias', [
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'estado' => 'presente',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('asistencia', ['id_inscripcion' => $inscripcion->id_inscripcion]);
    }

    public function test_no_puede_borrar_asistencia_de_curso_ajeno(): void
    {
        $this->actuarComoCatedratico(['asistencias.editar']);
        $asignacionAjena = Asignacion::factory()->create();
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacionAjena->id_asignacion]);
        $asistencia = Asistencia::factory()->create(['id_inscripcion' => $inscripcion->id_inscripcion]);

        $response = $this->deleteJson("/api/v1/asistencias/{$asistencia->id_asistencia}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('asistencia', ['id_asistencia' => $asistencia->id_asistencia]);
    }

    public function test_no_puede_editar_entrega_de_tarea_de_curso_ajeno_via_endpoint_generico(): void
    {
        $this->actuarComoCatedratico(['entregas.calificar']);
        $asignacionAjena = Asignacion::factory()->create();
        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacionAjena->id_asignacion, 'puntos' => 10]);
        $entrega = \App\Models\EntregaTarea::factory()->create(['id_tarea' => $tarea->id_tarea]);

        $response = $this->putJson("/api/v1/entregas-tarea/{$entrega->id_entrega}", ['calificacion' => 10]);

        $response->assertStatus(403);
        $this->assertNull($entrega->fresh()->calificacion);
    }

    public function test_no_puede_editar_detalle_de_calificacion_de_curso_ajeno(): void
    {
        $this->actuarComoCatedratico(['calificaciones.editar']);
        $asignacionAjena = Asignacion::factory()->create();
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacionAjena->id_asignacion]);
        $evaluacion = Evaluacion::factory()->create(['id_asignacion' => $asignacionAjena->id_asignacion]);
        $detalle = DetalleCalificacion::factory()->create([
            'id_evaluacion' => $evaluacion->id_evaluacion,
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'nota' => 50,
        ]);

        $response = $this->putJson("/api/v1/detalles-calificacion/{$detalle->id_detalle}", ['nota' => 100]);

        $response->assertStatus(403);
        $this->assertEquals(50, $detalle->fresh()->nota);
    }

    public function test_no_puede_editar_calificacion_final_de_curso_ajeno(): void
    {
        $this->actuarComoCatedratico(['calificaciones.editar']);
        $asignacionAjena = Asignacion::factory()->create();
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacionAjena->id_asignacion]);
        $final = CalificacionFinal::factory()->create(['id_inscripcion' => $inscripcion->id_inscripcion, 'nota_final' => 50]);

        $response = $this->putJson("/api/v1/calificaciones-finales/{$final->id_calificacion}", ['nota_final' => 100]);

        $response->assertStatus(403);
        $this->assertEquals(50, $final->fresh()->nota_final);
    }

    public function test_el_listado_de_tareas_solo_muestra_las_del_catedratico_autenticado(): void
    {
        $catedratico = $this->actuarComoCatedratico(['tareas.ver']);
        $asignacionPropia = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);
        $asignacionAjena = Asignacion::factory()->create();
        Tarea::factory()->create(['id_asignacion' => $asignacionPropia->id_asignacion, 'titulo' => 'Propia', 'puntos' => 10]);
        Tarea::factory()->create(['id_asignacion' => $asignacionAjena->id_asignacion, 'titulo' => 'Ajena', 'puntos' => 10]);

        $response = $this->getJson('/api/v1/tareas');

        $response->assertStatus(200);
        $titulos = collect($response->json('data'))->pluck('titulo');
        $this->assertTrue($titulos->contains('Propia'));
        $this->assertFalse($titulos->contains('Ajena'));
    }
}
