<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Grado;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Seccion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filtros del listado de alumnos (género, carrera, grado, sección y estado
 * académico) y el endpoint que da sus opciones a quien solo tiene el permiso
 * 'alumnos'.
 */
class AlumnoFiltrosTest extends TestCase
{
    use RefreshDatabase;

    private function actuarConPermisoAlumnos(): void
    {
        $usuario = Usuario::factory()->create();
        $rol = Rol::factory()->create(['nombre' => 'secretaria']);
        $permiso = Permiso::create(['nombre' => 'alumnos', 'descripcion' => 'Alumnos']);
        $rol->permisos()->attach($permiso->id_permiso);
        $usuario->roles()->attach($rol->id_rol);

        $this->actingAs($usuario, 'sanctum');
    }

    private function codigos(array $filtros): array
    {
        $codigos = collect($this->getJson('/api/v1/alumnos?'.http_build_query($filtros))
            ->assertOk()
            ->json('data'))
            ->pluck('codigo_mineduc')
            ->sort()
            ->values()
            ->all();

        return $codigos;
    }

    public function test_filtra_por_genero_carrera_grado_seccion_y_estado(): void
    {
        $this->actuarConPermisoAlumnos();

        $magisterio = Carrera::factory()->create();
        $bachillerato = Carrera::factory()->create();
        $cuarto = Grado::factory()->create(['nombre' => '4TO']);
        $quinto = Grado::factory()->create(['nombre' => '5TO']);
        $a = Seccion::factory()->create(['nombre' => 'A']);
        $b = Seccion::factory()->create(['nombre' => 'B']);

        Alumno::factory()->create(['codigo_mineduc' => 'A1', 'genero' => 'femenino', 'id_carrera' => $magisterio->id_carrera, 'id_grado_actual' => $cuarto->id_grado, 'id_seccion_actual' => $a->id_seccion]);
        Alumno::factory()->create(['codigo_mineduc' => 'A2', 'genero' => 'masculino', 'id_carrera' => $magisterio->id_carrera, 'id_grado_actual' => $quinto->id_grado, 'id_seccion_actual' => $b->id_seccion]);
        Alumno::factory()->create(['codigo_mineduc' => 'A3', 'genero' => 'femenino', 'id_carrera' => $bachillerato->id_carrera, 'id_grado_actual' => null, 'id_seccion_actual' => $a->id_seccion, 'estado_academico' => 'retirado']);
        Alumno::factory()->create(['codigo_mineduc' => 'A4', 'genero' => null, 'id_carrera' => $bachillerato->id_carrera, 'id_grado_actual' => $cuarto->id_grado, 'id_seccion_actual' => $a->id_seccion]);

        $this->assertSame(['A1', 'A2', 'A3', 'A4'], $this->codigos([]));
        $this->assertSame(['A1', 'A3'], $this->codigos(['genero' => 'femenino']));
        $this->assertSame(['A4'], $this->codigos(['genero' => 'sin_registrar']));
        $this->assertSame(['A1', 'A2'], $this->codigos(['id_carrera' => $magisterio->id_carrera]));
        $this->assertSame(['A1', 'A4'], $this->codigos(['id_grado' => $cuarto->id_grado]));
        $this->assertSame(['A3'], $this->codigos(['id_grado' => 'sin_grado']));
        $this->assertSame(['A2'], $this->codigos(['id_seccion' => $b->id_seccion]));
        $this->assertSame(['A3'], $this->codigos(['estado_academico' => 'retirado']));

        // Los filtros se combinan entre sí y con la búsqueda.
        $this->assertSame(['A1'], $this->codigos(['genero' => 'femenino', 'id_grado' => $cuarto->id_grado]));
        $this->assertSame(['A4'], $this->codigos(['id_carrera' => $bachillerato->id_carrera, 'q' => 'A4']));

        // Un valor que no corresponde se ignora en vez de romper la consulta.
        $this->assertSame(['A1', 'A2', 'A3', 'A4'], $this->codigos(['id_carrera' => 'abc', 'estado_academico' => 'x']));
    }

    public function test_da_las_opciones_de_los_filtros_con_solo_el_permiso_alumnos(): void
    {
        $this->actuarConPermisoAlumnos();

        $carrera = Carrera::factory()->create();
        $grado = Grado::factory()->create(['nombre' => '4TO']);
        $seccion = Seccion::factory()->create(['nombre' => 'A']);

        $this->getJson('/api/v1/alumnos/filtros')
            ->assertOk()
            ->assertJsonPath('carreras.0.id_carrera', $carrera->id_carrera)
            ->assertJsonPath('grados.0.id_grado', $grado->id_grado)
            ->assertJsonPath('secciones.0.id_seccion', $seccion->id_seccion);
    }
}
