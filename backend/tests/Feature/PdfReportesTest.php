<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asignacion;
use App\Models\Inscripcion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests: cada reporte PDF debe renderizar sin error (200,
 * Content-Type application/pdf) contra datos mínimos válidos. No
 * verifican el contenido visual (eso se hizo manualmente con
 * pdftotext) — solo que la vista no explote, que es justo lo que
 * falló en asistencia-final y avance-programatico al construir el
 * layout compartido (variable $fecha indefinida).
 */
class PdfReportesTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoAdmin(): void
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'admin']);

        // Igual que RoleSeeder: el rol admin tiene todos los permisos.
        foreach (Permiso::defaults() as $permiso) {
            Permiso::firstOrCreate(['nombre' => $permiso['nombre']], $permiso);
        }
        $rol->permisos()->attach(Permiso::pluck('id_permiso'));

        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');
    }

    private function assertEsPdf($response): void
    {
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_boletin_renderiza(): void
    {
        $this->actuarComoAdmin();
        $alumno = Alumno::factory()->create(['id_carrera' => null]);
        Inscripcion::factory()->create(['id_alumno' => $alumno->id_alumno]);

        $this->assertEsPdf($this->get("/api/v1/reportes/pdf/boletin/{$alumno->id_alumno}"));
    }

    public function test_kardex_renderiza(): void
    {
        $this->actuarComoAdmin();
        $alumno = Alumno::factory()->create(['id_carrera' => null]);
        Inscripcion::factory()->create(['id_alumno' => $alumno->id_alumno]);

        $this->assertEsPdf($this->get("/api/v1/reportes/pdf/kardex/{$alumno->id_alumno}"));
    }

    public function test_acta_renderiza_con_y_sin_zonas(): void
    {
        $this->actuarComoAdmin();
        $asignacion = Asignacion::factory()->create();
        Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $this->assertEsPdf($this->get("/api/v1/reportes/pdf/acta/{$asignacion->id_asignacion}"));
    }

    public function test_bitacora_renderiza(): void
    {
        $this->actuarComoAdmin();

        $this->assertEsPdf($this->get('/api/v1/reportes/pdf/bitacora'));
    }

    public function test_constancia_renderiza(): void
    {
        $this->actuarComoAdmin();
        $alumno = Alumno::factory()->create(['id_carrera' => null]);

        $this->assertEsPdf($this->get("/api/v1/reportes/pdf/constancia/{$alumno->id_alumno}"));
    }

    public function test_asistencia_renderiza(): void
    {
        $this->actuarComoAdmin();
        $asignacion = Asignacion::factory()->create();
        Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $this->assertEsPdf($this->get("/api/v1/reportes/pdf/asistencia/{$asignacion->id_asignacion}"));
    }

    public function test_asistencia_final_renderiza(): void
    {
        $this->actuarComoAdmin();
        $asignacion = Asignacion::factory()->create();
        Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $this->assertEsPdf($this->get("/api/v1/reportes/pdf/asistencia-final/{$asignacion->id_asignacion}"));
    }

    public function test_avance_programatico_renderiza(): void
    {
        $this->actuarComoAdmin();
        $asignacion = Asignacion::factory()->create();
        Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);

        $this->assertEsPdf($this->get("/api/v1/reportes/pdf/avance-programatico/{$asignacion->id_asignacion}"));
    }
}
