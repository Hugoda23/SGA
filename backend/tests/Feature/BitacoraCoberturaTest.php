<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\CalificacionFinal;
use App\Models\Inscripcion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gap encontrado en la auditoría de seguridad: LogsActivity (el trait que
 * escribe en Bitacora al crear/editar/borrar) solo estaba aplicado a 6
 * modelos — faltaban justo los que más importa auditar: notas, asistencia,
 * tareas y permisos de rol. Si alguien alteraba una nota o le quitaba
 * permisos a un rol, no quedaba ningún rastro.
 */
class BitacoraCoberturaTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoAdmin(array $permisos = []): Usuario
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'admin']);
        foreach ($permisos as $nombre) {
            $permiso = Permiso::create(['nombre' => $nombre, 'descripcion' => $nombre]);
            $rol->permisos()->attach($permiso->id_permiso);
        }
        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');

        return $usuario;
    }

    public function test_editar_una_calificacion_final_queda_registrado_en_bitacora(): void
    {
        $usuario = $this->actuarComoAdmin(['calificaciones']);
        $asignacion = Asignacion::factory()->create();
        $inscripcion = Inscripcion::factory()->create(['id_asignacion' => $asignacion->id_asignacion]);
        $final = CalificacionFinal::factory()->create(['id_inscripcion' => $inscripcion->id_inscripcion, 'nota_final' => 60]);

        $this->putJson("/api/v1/calificaciones-finales/{$final->id_calificacion}", ['nota_final' => 95])
            ->assertStatus(200);

        $this->assertDatabaseHas('bitacora', [
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'ACTUALIZAR',
            'tabla_afectada' => 'calificacion_final',
            'id_registro' => $final->id_calificacion,
        ]);
    }

    public function test_reasignar_permisos_de_un_rol_queda_registrado_en_bitacora(): void
    {
        $usuario = $this->actuarComoAdmin(['roles']);
        $rolObjetivo = Rol::factory()->create(['nombre' => 'secretaria']);
        $permiso = Permiso::create(['nombre' => 'alumnos.ver', 'descripcion' => 'Ver alumnos']);

        $this->postJson("/api/v1/roles/{$rolObjetivo->id_rol}/permisos", [
            'permiso_ids' => [$permiso->id_permiso],
        ])->assertStatus(200);

        $this->assertDatabaseHas('bitacora', [
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'ACTUALIZAR',
            'tabla_afectada' => 'rol_permiso',
            'id_registro' => $rolObjetivo->id_rol,
        ]);
    }
}
