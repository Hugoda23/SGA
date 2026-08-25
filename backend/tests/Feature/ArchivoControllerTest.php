<?php

namespace Tests\Feature;

use App\Models\Archivo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchivoControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoUsuarioConPermisoArchivos(): void
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'admin']);
        $permiso = Permiso::create(['nombre' => 'archivos', 'descripcion' => 'Archivos']);
        $rol->permisos()->attach($permiso->id_permiso);
        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');
    }

    public function test_sube_un_archivo_valido(): void
    {
        Storage::fake('public');
        $this->actuarComoUsuarioConPermisoArchivos();

        $archivo = UploadedFile::fake()->create('tarea.pdf', 500, 'application/pdf');

        $response = $this->postJson('/api/v1/archivos/upload', ['archivo' => $archivo]);

        $response->assertStatus(201);
        $ruta = $response->json('ruta');
        Storage::disk('public')->assertExists($ruta);
    }

    public function test_rechaza_un_tipo_de_archivo_no_permitido(): void
    {
        Storage::fake('public');
        $this->actuarComoUsuarioConPermisoArchivos();

        $archivo = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');

        $response = $this->postJson('/api/v1/archivos/upload', ['archivo' => $archivo]);

        $response->assertStatus(422)->assertJsonValidationErrors('archivo');
    }

    public function test_eliminar_archivo_borra_el_archivo_fisico(): void
    {
        Storage::fake('public');
        $this->actuarComoUsuarioConPermisoArchivos();

        $ruta = 'archivos/prueba.pdf';
        Storage::disk('public')->put($ruta, 'contenido');
        $archivo = Archivo::factory()->create(['ruta' => $ruta]);

        $response = $this->deleteJson("/api/v1/archivos/{$archivo->id_archivo}");

        $response->assertStatus(204);
        Storage::disk('public')->assertMissing($ruta);
        $this->assertDatabaseMissing('archivo', ['id_archivo' => $archivo->id_archivo]);
    }
}
