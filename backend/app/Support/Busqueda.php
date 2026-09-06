<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Búsqueda de texto tolerante para los listados de la API.
 *
 * Los buscadores comparaban con 'ilike', que ignora mayúsculas pero nada más.
 * Escribir "jose munoz" no encontraba a "José Muñoz", y "Juan Pérez" tampoco lo
 * encontraba: el patrón completo se comparaba contra cada columna por separado,
 * y el nombre está en 'nombre' y el apellido en 'apellido'.
 *
 * Esta clase resuelve las dos cosas:
 *
 *  - Compara sin tildes ni mayúsculas, con unaccent(lower(...)) en ambos lados.
 *    unaccent también convierte ñ→n, así que "munoz" encuentra "Muñoz".
 *  - Parte el término en palabras: cada palabra debe aparecer en alguna de las
 *    columnas (AND entre palabras, OR entre columnas). Así "perez juan" y
 *    "juan perez" encuentran al mismo alumno.
 *
 * Los campos pueden ser columnas propias ('nombre') o de una relación
 * ('alumno.nombre', 'asignacion.curso.nombre_curso').
 *
 * Requiere la extensión unaccent, que habilita la migración
 * 2026_09_06_120000_enable_unaccent_extension.
 */
class Busqueda
{
    /**
     * @param  array<int, string>  $campos
     */
    public static function aplicar(Builder $query, string $termino, array $campos): Builder
    {
        $palabras = self::palabras($termino);

        if ($palabras === [] || $campos === []) {
            return $query;
        }

        foreach ($palabras as $palabra) {
            $query->where(function (Builder $grupo) use ($palabra, $campos) {
                $patron = '%'.self::escaparComodines($palabra).'%';
                $primero = true;

                foreach ($campos as $campo) {
                    $separador = strrpos($campo, '.');

                    if ($separador === false) {
                        self::comparar($grupo, $campo, $patron, ! $primero);
                    } else {
                        $relacion = substr($campo, 0, $separador);
                        $columna = substr($campo, $separador + 1);

                        $grupo->{$primero ? 'whereHas' : 'orWhereHas'}(
                            $relacion,
                            fn (Builder $relacionada) => self::comparar($relacionada, $columna, $patron, false)
                        );
                    }

                    $primero = false;
                }
            });
        }

        return $query;
    }

    /**
     * Separa el término en palabras y descarta los espacios de más, para que
     * "  Juan   Pérez " se busque igual que "Juan Pérez".
     *
     * @return array<int, string>
     */
    protected static function palabras(string $termino): array
    {
        $limpio = trim($termino);

        if ($limpio === '') {
            return [];
        }

        return preg_split('/\s+/u', $limpio, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Escapa los comodines de LIKE. Sin esto, buscar "%" listaba todo y "_"
     * hacía de comodín de un carácter en vez de buscarse literalmente.
     */
    protected static function escaparComodines(string $palabra): string
    {
        return addcslashes($palabra, '\\%_');
    }

    /**
     * Compara una columna contra el patrón, sin tildes ni mayúsculas.
     *
     * La columna se califica con su tabla porque dentro de whereHas la
     * subconsulta une la tabla padre y un nombre suelto podría ser ambiguo.
     * El cast a text permite buscar también en columnas que no son de texto.
     */
    protected static function comparar(Builder $builder, string $columna, string $patron, bool $o): void
    {
        $calificada = $builder->getQuery()->getGrammar()->wrap(
            $builder->getModel()->getTable().'.'.$columna
        );

        $sql = "unaccent(lower(cast({$calificada} as text))) like unaccent(lower(?))";

        $o ? $builder->orWhereRaw($sql, [$patron]) : $builder->whereRaw($sql, [$patron]);
    }
}
