<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Support\Busqueda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los buscadores de los listados no deben depender de que el usuario escriba
 * las tildes, las mayúsculas ni el orden exacto de nombre y apellido.
 *
 * Estos tests también avisan si falta la extensión unaccent en la base: sin
 * ella, todas las consultas de búsqueda fallarían.
 */
class BusquedaFlexibleTest extends TestCase
{
    use RefreshDatabase;

    private function buscar(string $termino): array
    {
        return Busqueda::aplicar(
            Alumno::query(),
            $termino,
            ['nombre', 'apellido', 'codigo_mineduc']
        )->pluck('codigo_mineduc')->all();
    }

    private function crearAlumno(string $nombre, string $apellido, string $codigo): void
    {
        Alumno::factory()->create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'codigo_mineduc' => $codigo,
        ]);
    }

    public function test_encuentra_sin_tildes_sin_mayusculas_y_sin_la_enye(): void
    {
        $this->crearAlumno('José', 'Muñoz Velásquez', 'A-1');
        $this->crearAlumno('Ana', 'Pérez', 'A-2');

        foreach (['jose', 'JOSE', 'José', 'munoz', 'MUÑOZ', 'velasquez'] as $termino) {
            $this->assertSame(['A-1'], $this->buscar($termino), "falló buscando '{$termino}'");
        }
    }

    public function test_encuentra_aunque_el_nombre_y_el_apellido_esten_en_columnas_distintas(): void
    {
        $this->crearAlumno('José', 'Muñoz Velásquez', 'A-1');
        $this->crearAlumno('José', 'Pérez', 'A-2');

        // El nombre está en 'nombre' y el apellido en 'apellido': antes esto no
        // devolvía nada porque el término completo se buscaba en cada columna.
        $this->assertSame(['A-1'], $this->buscar('jose munoz'));

        // El orden no importa, y los espacios de más tampoco.
        $this->assertSame(['A-1'], $this->buscar('munoz jose'));
        $this->assertSame(['A-1'], $this->buscar('  jose   munoz  '));
    }

    public function test_cada_palabra_debe_aparecer_para_que_haya_coincidencia(): void
    {
        $this->crearAlumno('José', 'Muñoz', 'A-1');

        $this->assertSame([], $this->buscar('jose ramirez'));
    }

    public function test_los_comodines_de_like_se_buscan_literalmente(): void
    {
        $this->crearAlumno('José', 'Muñoz', 'A-1');

        // Sin escapar, '%' y '_' harían de comodín y listarían a todo el mundo.
        $this->assertSame([], $this->buscar('%'));
        $this->assertSame([], $this->buscar('jos_'));
    }

    public function test_un_termino_vacio_no_filtra(): void
    {
        $this->crearAlumno('José', 'Muñoz', 'A-1');
        $this->crearAlumno('Ana', 'Pérez', 'A-2');

        $this->assertCount(2, $this->buscar('   '));
    }
}
