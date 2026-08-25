<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\Inscripcion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug de seguridad encontrado y corregido: PdfReportController no
 * verificaba que el usuario autenticado tuviera derecho a ver el
 * alumno/asignación solicitado. Cualquier usuario autenticado (incluido
 * un alumno) podía descargar el boletín, kárdex, constancia, acta o
 * control de asistencia de cualquier persona solo cambiando el ID en
 * la URL.
 */
class SeguridadReportesPdfTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComo(string $rolNombre): Usuario
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::firstOrCreate(['nombre' => $rolNombre], ['descripcion' => $rolNombre]);
        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');

        return $usuario;
    }

    public function test_alumno_no_puede_descargar_boletin_de_otro_alumno(): void
    {
        $usuario = $this->actuarComo('alumno');
        Alumno::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $otroAlumno = Alumno::factory()->create();

        $response = $this->get("/api/v1/reportes/pdf/boletin/{$otroAlumno->id_alumno}");

        $response->assertStatus(403);
    }

    public function test_alumno_si_puede_descargar_su_propio_boletin(): void
    {
        $usuario = $this->actuarComo('alumno');
        $alumno = Alumno::factory()->create(['id_usuario' => $usuario->id_usuario]);

        $response = $this->get("/api/v1/reportes/pdf/boletin/{$alumno->id_alumno}");

        $response->assertStatus(200);
    }

    public function test_alumno_no_puede_descargar_kardex_ni_constancia_de_otro_alumno(): void
    {
        $usuario = $this->actuarComo('alumno');
        Alumno::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $otroAlumno = Alumno::factory()->create();

        $this->get("/api/v1/reportes/pdf/kardex/{$otroAlumno->id_alumno}")->assertStatus(403);
        $this->get("/api/v1/reportes/pdf/constancia/{$otroAlumno->id_alumno}")->assertStatus(403);
    }

    public function test_catedratico_no_puede_descargar_acta_de_curso_ajeno(): void
    {
        $usuario = $this->actuarComo('catedratico');
        Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $asignacionAjena = Asignacion::factory()->create();

        $response = $this->get("/api/v1/reportes/pdf/acta/{$asignacionAjena->id_asignacion}");

        $response->assertStatus(403);
    }

    public function test_catedratico_dueno_si_puede_descargar_acta_de_su_curso(): void
    {
        $usuario = $this->actuarComo('catedratico');
        $catedratico = Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $asignacion = Asignacion::factory()->create(['id_catedratico' => $catedratico->id_catedratico]);
        Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $response = $this->get("/api/v1/reportes/pdf/acta/{$asignacion->id_asignacion}");

        $response->assertStatus(200);
    }

    public function test_catedratico_no_puede_descargar_asistencia_de_curso_ajeno(): void
    {
        $usuario = $this->actuarComo('catedratico');
        Catedratico::factory()->create(['id_usuario' => $usuario->id_usuario]);
        $asignacionAjena = Asignacion::factory()->create();

        $this->get("/api/v1/reportes/pdf/asistencia/{$asignacionAjena->id_asignacion}")->assertStatus(403);
        $this->get("/api/v1/reportes/pdf/asistencia-final/{$asignacionAjena->id_asignacion}")->assertStatus(403);
        $this->get("/api/v1/reportes/pdf/avance-programatico/{$asignacionAjena->id_asignacion}")->assertStatus(403);
    }

    public function test_bitacora_requiere_permiso_explicito(): void
    {
        $this->actuarComo('alumno');
        $this->get('/api/v1/reportes/pdf/bitacora')->assertStatus(403);
    }

    public function test_admin_puede_descargar_cualquier_reporte(): void
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::firstOrCreate(['nombre' => 'admin'], ['descripcion' => 'admin']);
        foreach (Permiso::defaults() as $permiso) {
            Permiso::firstOrCreate(['nombre' => $permiso['nombre']], $permiso);
        }
        $rol->permisos()->syncWithoutDetaching(Permiso::pluck('id_permiso'));
        $usuario->roles()->attach($rol->id_rol);
        $this->actingAs($usuario, 'sanctum');

        $alumno = Alumno::factory()->create();
        $asignacion = Asignacion::factory()->create();
        Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $this->get("/api/v1/reportes/pdf/boletin/{$alumno->id_alumno}")->assertStatus(200);
        $this->get("/api/v1/reportes/pdf/acta/{$asignacion->id_asignacion}")->assertStatus(200);
        $this->get('/api/v1/reportes/pdf/bitacora')->assertStatus(200);
    }
}
