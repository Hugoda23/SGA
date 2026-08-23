<?php

namespace Tests\Feature;

use App\Models\Archivo;
use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\Material;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoCatedraticoDe(Asignacion $asignacion): void
    {
        $usuario = Usuario::factory()->create();
        $catedratico = Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $asignacion->update(['id_catedratico' => $catedratico->id_catedratico]);

        $this->actingAs($usuario, 'sanctum');
    }

    public function test_crea_material_de_tipo_enlace(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);

        $response = $this->postJson('/api/v1/materiales', [
            'id_asignacion' => $asignacion->id_asignacion,
            'titulo' => 'Guía de estudio',
            'tipo' => 'enlace',
            'url' => 'https://example.com/guia.pdf',
        ]);

        $response->assertStatus(201)->assertJsonPath('tipo', 'enlace');
        $this->assertDatabaseHas('material', ['titulo' => 'Guía de estudio', 'url' => 'https://example.com/guia.pdf']);
    }

    public function test_rechaza_material_de_tipo_enlace_sin_url(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);

        $response = $this->postJson('/api/v1/materiales', [
            'id_asignacion' => $asignacion->id_asignacion,
            'titulo' => 'Guía sin url',
            'tipo' => 'enlace',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('url');
    }

    public function test_crea_material_de_tipo_archivo_y_lo_sube_a_storage(): void
    {
        Storage::fake('public');
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);

        $archivo = UploadedFile::fake()->create('apuntes.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/v1/materiales', [
            'id_asignacion' => $asignacion->id_asignacion,
            'titulo' => 'Apuntes de clase',
            'tipo' => 'archivo',
            'archivo' => $archivo,
        ]);

        $response->assertStatus(201);
        $material = Material::where('titulo', 'Apuntes de clase')->first();
        $this->assertNotNull($material->id_archivo);
        Storage::disk('public')->assertExists($material->archivo->ruta);
    }

    public function test_rechaza_un_tipo_de_archivo_no_permitido(): void
    {
        Storage::fake('public');
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);

        $archivo = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');

        $response = $this->postJson('/api/v1/materiales', [
            'id_asignacion' => $asignacion->id_asignacion,
            'titulo' => 'Material sospechoso',
            'tipo' => 'archivo',
            'archivo' => $archivo,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('archivo');
    }

    public function test_elimina_material_borra_tambien_el_archivo_de_storage(): void
    {
        Storage::fake('public');
        $asignacion = Asignacion::factory()->create();
        $this->actuarComoCatedraticoDe($asignacion);

        $ruta = 'materiales/prueba.pdf';
        Storage::disk('public')->put($ruta, 'contenido');
        $archivoModelo = Archivo::factory()->create(['ruta' => $ruta]);
        $material = Material::create([
            'id_asignacion' => $asignacion->id_asignacion,
            'titulo' => 'A eliminar',
            'tipo' => 'archivo',
            'id_archivo' => $archivoModelo->id_archivo,
        ]);

        $response = $this->deleteJson("/api/v1/materiales/{$material->id_material}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('material', ['id_material' => $material->id_material]);
        $this->assertDatabaseMissing('archivo', ['id_archivo' => $archivoModelo->id_archivo]);
        Storage::disk('public')->assertMissing($ruta);
    }
}
