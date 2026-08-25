<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Archivo;
use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\Inscripcion;
use App\Models\Material;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bug de seguridad encontrado y corregido: GET /archivos/{archivo}/descargar
 * no tenía middleware de permiso ni verificación de dueño. Como el
 * modelo Archivo no guarda quién lo subió, cualquier usuario autenticado
 * (incluido un alumno) podía descargar cualquier archivo del sistema
 * (materiales de cursos ajenos, tareas de otros alumnos) solo cambiando
 * el ID en la URL.
 */
class SeguridadArchivosTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComo(string $rolNombre): Usuario
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::firstOrCreate(['nombre' => $rolNombre], ['descripcion' => $rolNombre]);
        $permiso = Permiso::firstOrCreate(['nombre' => 'archivos'], ['descripcion' => 'Archivos']);
        $rol->permisos()->syncWithoutDetaching([$permiso->id_permiso]);
        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');

        return $usuario;
    }

    private function materialConArchivo(int $idAsignacion): Archivo
    {
        Storage::fake('public');
        $archivo = Archivo::factory()->create();

        Material::create([
            'id_asignacion' => $idAsignacion,
            'titulo' => 'Material de prueba',
            'tipo' => 'archivo',
            'id_archivo' => $archivo->id_archivo,
            'fecha_publicacion' => now(),
        ]);

        Storage::disk('public')->put($archivo->ruta, 'contenido');

        return $archivo;
    }

    public function test_alumno_no_inscrito_no_puede_descargar_material_de_curso_ajeno(): void
    {
        $usuario = $this->actuarComo('alumno');
        Alumno::factory()->create(['id_usuario' => $usuario->id_usuario]);

        $asignacion = Asignacion::factory()->create();
        $archivo = $this->materialConArchivo($asignacion->id_asignacion);

        $response = $this->get("/api/v1/archivos/{$archivo->id_archivo}/descargar");

        $response->assertStatus(403);
    }

    public function test_alumno_inscrito_si_puede_descargar_material_de_su_curso(): void
    {
        $usuario = $this->actuarComo('alumno');
        $alumno = Alumno::factory()->create(['id_usuario' => $usuario->id_usuario]);

        $asignacion = Asignacion::factory()->create();
        Inscripcion::factory()->create([
            'id_alumno' => $alumno->id_alumno,
            'id_asignacion' => $asignacion->id_asignacion,
        ]);
        $archivo = $this->materialConArchivo($asignacion->id_asignacion);

        $response = $this->get("/api/v1/archivos/{$archivo->id_archivo}/descargar");

        $response->assertStatus(200);
    }

    public function test_catedratico_no_puede_descargar_material_de_curso_ajeno(): void
    {
        $usuario = $this->actuarComo('catedratico');
        Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);

        $asignacionAjena = Asignacion::factory()->create();
        $archivo = $this->materialConArchivo($asignacionAjena->id_asignacion);

        $response = $this->get("/api/v1/archivos/{$archivo->id_archivo}/descargar");

        $response->assertStatus(403);
    }

    public function test_catedratico_dueno_si_puede_descargar_material_de_su_curso(): void
    {
        $usuario = $this->actuarComo('catedratico');
        $catedratico = Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);
        $archivo = $this->materialConArchivo($asignacion->id_asignacion);

        $response = $this->get("/api/v1/archivos/{$archivo->id_archivo}/descargar");

        $response->assertStatus(200);
    }

    public function test_admin_puede_descargar_cualquier_archivo(): void
    {
        $this->actuarComo('admin');
        $asignacion = Asignacion::factory()->create();
        $archivo = $this->materialConArchivo($asignacion->id_asignacion);

        $response = $this->get("/api/v1/archivos/{$archivo->id_archivo}/descargar");

        $response->assertStatus(200);
    }

    public function test_archivo_sin_material_asociado_solo_lo_descarga_personal_administrativo(): void
    {
        Storage::fake('public');
        $archivo = Archivo::factory()->create();
        Storage::disk('public')->put($archivo->ruta, 'contenido');

        $this->actuarComo('alumno');
        $response = $this->get("/api/v1/archivos/{$archivo->id_archivo}/descargar");
        $response->assertStatus(403);

        $this->actuarComo('admin');
        $response = $this->get("/api/v1/archivos/{$archivo->id_archivo}/descargar");
        $response->assertStatus(200);
    }
}
