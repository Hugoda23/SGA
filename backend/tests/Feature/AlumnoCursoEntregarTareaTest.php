<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asignacion;
use App\Models\EntregaTarea;
use App\Models\Inscripcion;
use App\Models\Tarea;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlumnoCursoEntregarTareaTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoAlumnoInscritoEn(Asignacion $asignacion): Alumno
    {
        $usuario = Usuario::factory()->create();
        $alumno = Alumno::factory()->create(['id_usuario' => $usuario->id_usuario]);
        Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_alumno' => $alumno->id_alumno]);

        $this->actingAs($usuario, 'sanctum');

        return $alumno;
    }

    public function test_alumno_inscrito_puede_entregar_un_archivo(): void
    {
        Storage::fake('public');
        $asignacion = Asignacion::factory()->create();
        $alumno = $this->actuarComoAlumnoInscritoEn($asignacion);
        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 10]);

        $response = $this->postJson("/api/v1/alumno/curso/{$asignacion->id_asignacion}/entregar/{$tarea->id_tarea}", [
            'archivo' => UploadedFile::fake()->create('tarea.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('entrega_tarea', [
            'id_tarea' => $tarea->id_tarea,
            'id_alumno' => $alumno->id_alumno,
            'estado' => 'borrador',
        ]);
    }

    public function test_alumno_no_inscrito_no_puede_entregar(): void
    {
        $asignacion = Asignacion::factory()->create();
        $usuario = Usuario::factory()->create();
        Alumno::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $this->actingAs($usuario, 'sanctum');
        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 10]);

        $response = $this->postJson("/api/v1/alumno/curso/{$asignacion->id_asignacion}/entregar/{$tarea->id_tarea}", [
            'link' => 'https://example.com/tarea',
        ]);

        $response->assertStatus(403);
    }

    public function test_rechaza_tarea_que_no_pertenece_al_curso(): void
    {
        $asignacion = Asignacion::factory()->create();
        $otraAsignacion = Asignacion::factory()->create();
        $this->actuarComoAlumnoInscritoEn($asignacion);
        $tareaDeOtroCurso = Tarea::factory()->create(['id_asignacion' => $otraAsignacion->id_asignacion, 'puntos' => 10]);

        $response = $this->postJson("/api/v1/alumno/curso/{$asignacion->id_asignacion}/entregar/{$tareaDeOtroCurso->id_tarea}", [
            'link' => 'https://example.com/tarea',
        ]);

        $response->assertStatus(404);
    }

    public function test_rechaza_entrega_por_link_si_la_tarea_no_lo_permite(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoAlumnoInscritoEn($asignacion);
        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 10, 'permitir_link' => false]);

        $response = $this->postJson("/api/v1/alumno/curso/{$asignacion->id_asignacion}/entregar/{$tarea->id_tarea}", [
            'link' => 'https://example.com/tarea',
        ]);

        $response->assertStatus(422);
    }

    public function test_reemplazar_un_archivo_por_un_link_limpia_el_archivo_anterior(): void
    {
        Storage::fake('public');
        $asignacion = Asignacion::factory()->create();
        $alumno = $this->actuarComoAlumnoInscritoEn($asignacion);
        $tarea = Tarea::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'puntos' => 10, 'permitir_link' => true]);
        EntregaTarea::factory()->create([
            'id_tarea' => $tarea->id_tarea,
            'id_alumno' => $alumno->id_alumno,
            'archivo' => 'entregas/anterior.pdf',
            'link' => null,
        ]);

        $response = $this->postJson("/api/v1/alumno/curso/{$asignacion->id_asignacion}/entregar/{$tarea->id_tarea}", [
            'link' => 'https://example.com/nueva-entrega',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('entrega_tarea', [
            'id_tarea' => $tarea->id_tarea,
            'id_alumno' => $alumno->id_alumno,
            'link' => 'https://example.com/nueva-entrega',
            'archivo' => null,
        ]);
    }
}
