<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Habilita unaccent, la extensión que quita las tildes dentro de la propia
     * consulta SQL.
     *
     * Los buscadores usaban 'ilike', que ignora mayúsculas pero no acentos: al
     * escribir "jose munoz" no aparecía "José Muñoz". Con unaccent podemos
     * comparar unaccent(lower(columna)) contra unaccent(lower(patrón)) y que
     * ambos lados queden sin tildes y en minúscula. Ver App\Support\Busqueda.
     *
     * unaccent es una extensión "trusted" desde PostgreSQL 13, así que el dueño
     * de la base la puede crear sin ser superusuario.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
    }

    /**
     * No se elimina la extensión: otras consultas podrían depender de ella y
     * DROP EXTENSION fallaría o rompería los buscadores en un rollback parcial.
     */
    public function down(): void
    {
        // Intencionalmente vacío.
    }
};
