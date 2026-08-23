<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Anuncio;
use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\Inscripcion;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnuncioControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoCatedraticoDe(Asignacion $asignacion): void
    {
        $usuario = Usuario::factory()->create();
        $catedratico = Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $asignacion->update(['id_catedratico' => $catedratico->id_catedratico]);

        $this->actingAs($usuario, 'sanctum');
    }

    public function test_crea_un_anuncio_y_notifica_a_los_alumnos_inscritos(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);

        $alumnoUsuario = Usuario::factory()->create();
        $alumno = Alumno::factory()->create(['id_usuario' => $alumnoUsuario->id_usuario]);
        Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion, 'id_alumno' => $alumno->id_alumno]);

        $response = $this->postJson('/api/v1/anuncios', [
            'id_asignacion' => $asignacion->id_asignacion,
            'titulo' => 'Suspensión de clase',
            'contenido' => 'No hay clase el viernes.',
        ]);

        $response->assertStatus(201)->assertJsonPath('titulo', 'Suspensión de clase');
        $this->assertDatabaseHas('notificacion', ['id_usuario' => $alumnoUsuario->id_usuario]);
    }

    public function test_rechaza_anuncio_sin_titulo(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);

        $response = $this->postJson('/api/v1/anuncios', [
            'id_asignacion' => $asignacion->id_asignacion,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('titulo');
    }

    public function test_actualiza_un_anuncio(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);
        $anuncio = Anuncio::create(['id_asignacion' => $asignacion->id_asignacion, 'titulo' => 'Original']);

        $response = $this->putJson("/api/v1/anuncios/{$anuncio->id_anuncio}", [
            'titulo' => 'Editado',
        ]);

        $response->assertStatus(200)->assertJsonPath('titulo', 'Editado');
    }

    public function test_elimina_un_anuncio(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);
        $anuncio = Anuncio::create(['id_asignacion' => $asignacion->id_asignacion, 'titulo' => 'A borrar']);

        $response = $this->deleteJson("/api/v1/anuncios/{$anuncio->id_anuncio}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('anuncio', ['id_anuncio' => $anuncio->id_anuncio]);
    }
}
