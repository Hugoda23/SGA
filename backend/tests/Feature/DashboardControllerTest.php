<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Catedratico;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_devuelve_los_conteos_y_el_agrupado_por_carrera_correctos(): void
    {
        $this->actingAs(Usuario::factory()->create(), 'sanctum');

        $carreraA = Carrera::factory()->create(['nombre_carrera' => 'Perito Contador']);
        $carreraB = Carrera::factory()->create(['nombre_carrera' => 'Bachillerato']);
        $alumnos = Alumno::factory()->count(2)->create(['id_carrera' => $carreraA->id_carrera])
            ->concat(Alumno::factory()->count(1)->create(['id_carrera' => $carreraB->id_carrera]));
        Catedratico::factory()->count(3)->create();
        Curso::factory()->count(4)->create();

        // Cada inscripción con su propia asignación (por defecto de fábrica)
        // para no chocar con el índice único (id_alumno, id_asignacion) al
        // reutilizar solo 3 alumnos en 5 inscripciones.
        foreach (range(0, 4) as $i) {
            Inscripcion::factory()->create(['id_alumno' => $alumnos[$i % 3]->id_alumno]);
        }

        $response = $this->getJson('/api/v1/dashboard/stats');

        // Se compara contra el conteo real en BD (no un número fijo) porque
        // las fábricas encadenan relaciones por defecto (ej. Inscripcion
        // crea su propia Asignacion, que a su vez crea su propio Curso) —
        // lo que importa verificar es que el endpoint refleje la BD, no
        // controlar cuántas filas de más generan esas cadenas.
        $response->assertStatus(200);
        $response->assertJsonPath('metrics.alumnos', Alumno::count());
        $response->assertJsonPath('metrics.catedraticos', Catedratico::count());
        $response->assertJsonPath('metrics.cursos', Curso::count());
        $response->assertJsonPath('metrics.inscripciones', Inscripcion::count());
        $this->assertEquals(3, Alumno::count());
        $this->assertEquals(5, Inscripcion::count());

        $porCarrera = collect($response->json('charts.alumnosPorCarrera'))->keyBy('name');
        $this->assertEquals(2, $porCarrera['Perito Contador']['value']);
        $this->assertEquals(1, $porCarrera['Bachillerato']['value']);
    }
}
