<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Aula;
use App\Models\Catedratico;
use App\Models\Curso;
use App\Models\Grado;
use App\Models\Pensum;
use App\Models\PeriodoAcademico;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsignacionCoherenciaPensumTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoAdmin(): void
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'admin']);
        $permiso = Permiso::create(['nombre' => 'asignaciones', 'descripcion' => 'Asignaciones']);
        $rol->permisos()->attach($permiso->id_permiso);
        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');
    }

    private function datosBase(): array
    {
        return [
            'id_catedratico' => Catedratico::factory()->create()->id_catedratico,
            'id_aula' => Aula::factory()->create()->id_aula,
            'id_periodo' => PeriodoAcademico::factory()->create()->id_periodo,
        ];
    }

    public function test_rechaza_crear_asignacion_para_un_curso_que_no_esta_en_el_pensum_del_grado(): void
    {
        $this->actuarComoAdmin();
        $curso = Curso::factory()->create();
        $grado = Grado::factory()->create();

        $response = $this->postJson('/api/v1/asignaciones', array_merge($this->datosBase(), [
            'id_curso' => $curso->id_curso,
            'id_grado' => $grado->id_grado,
        ]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('asignacion', ['id_curso' => $curso->id_curso, 'id_grado' => $grado->id_grado]);
    }

    public function test_permite_crear_asignacion_cuando_el_curso_si_esta_en_el_pensum_del_grado(): void
    {
        $this->actuarComoAdmin();
        $curso = Curso::factory()->create();
        $grado = Grado::factory()->create();
        Pensum::factory()->create(['id_curso' => $curso->id_curso, 'id_grado' => $grado->id_grado]);

        $response = $this->postJson('/api/v1/asignaciones', array_merge($this->datosBase(), [
            'id_curso' => $curso->id_curso,
            'id_grado' => $grado->id_grado,
        ]));

        $response->assertStatus(201);
    }

    public function test_permite_crear_asignacion_sin_grado_sin_validar_pensum(): void
    {
        $this->actuarComoAdmin();
        $curso = Curso::factory()->create();

        $response = $this->postJson('/api/v1/asignaciones', array_merge($this->datosBase(), [
            'id_curso' => $curso->id_curso,
        ]));

        $response->assertStatus(201);
    }

    public function test_rechaza_actualizar_asignacion_a_un_grado_fuera_del_pensum_del_curso(): void
    {
        $this->actuarComoAdmin();
        $curso = Curso::factory()->create();
        $gradoValido = Grado::factory()->create();
        $gradoInvalido = Grado::factory()->create();
        Pensum::factory()->create(['id_curso' => $curso->id_curso, 'id_grado' => $gradoValido->id_grado]);

        $asignacion = Asignacion::factory()->create(['id_curso' => $curso->id_curso, 'id_grado' => $gradoValido->id_grado]);

        $response = $this->putJson("/api/v1/asignaciones/{$asignacion->id_asignacion}", [
            'id_grado' => $gradoInvalido->id_grado,
        ]);

        $response->assertStatus(422);
        $this->assertEquals($gradoValido->id_grado, $asignacion->fresh()->id_grado);
    }

    public function test_permite_editar_otro_campo_de_una_asignacion_ya_existente_fuera_del_pensum(): void
    {
        $this->actuarComoAdmin();
        // Datos legados: la asignación ya existía con una combinación
        // curso/grado que no está en el pensum (puede pasar si el pensum se
        // pobló después). Editar aula/catedrático no debe bloquearse por eso.
        $curso = Curso::factory()->create();
        $grado = Grado::factory()->create();
        $asignacion = Asignacion::factory()->create(['id_curso' => $curso->id_curso, 'id_grado' => $grado->id_grado]);
        $nuevaAula = Aula::factory()->create();

        $response = $this->putJson("/api/v1/asignaciones/{$asignacion->id_asignacion}", [
            'id_aula' => $nuevaAula->id_aula,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($nuevaAula->id_aula, $asignacion->fresh()->id_aula);
    }
}
