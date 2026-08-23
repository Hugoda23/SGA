<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\HorarioDetalle;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioDetalleRangoHorasTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoAdmin(): void
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'admin']);
        $permiso = Permiso::create(['nombre' => 'horarios', 'descripcion' => 'Horarios']);
        $rol->permisos()->attach($permiso->id_permiso);
        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');
    }

    public function test_rechaza_crear_horario_con_hora_fin_antes_o_igual_a_hora_inicio(): void
    {
        $this->actuarComoAdmin();
        $asignacion = Asignacion::factory()->create();

        $response = $this->postJson('/api/v1/horarios', [
            'id_asignacion' => $asignacion->id_asignacion,
            'dia_semana' => 'lunes',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '09:00:00',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('horario_detalle', ['id_asignacion' => $asignacion->id_asignacion]);
    }

    public function test_permite_crear_horario_con_rango_de_horas_valido(): void
    {
        $this->actuarComoAdmin();
        $asignacion = Asignacion::factory()->create();

        $response = $this->postJson('/api/v1/horarios', [
            'id_asignacion' => $asignacion->id_asignacion,
            'dia_semana' => 'lunes',
            'hora_inicio' => '08:00:00',
            'hora_fin' => '09:00:00',
        ]);

        $response->assertStatus(201);
    }

    public function test_rechaza_actualizar_horario_a_un_rango_invalido(): void
    {
        $this->actuarComoAdmin();
        $asignacion = Asignacion::factory()->create();
        $horario = HorarioDetalle::factory()->create([
            'id_asignacion' => $asignacion->id_asignacion,
            'dia_semana' => 'martes',
            'hora_inicio' => '08:00:00',
            'hora_fin' => '09:00:00',
        ]);

        $response = $this->putJson("/api/v1/horarios/{$horario->id_horario}", [
            'hora_inicio' => '09:00:00',
        ]);

        $response->assertStatus(422);
        $this->assertEquals('08:00:00', $horario->fresh()->hora_inicio);
    }
}
