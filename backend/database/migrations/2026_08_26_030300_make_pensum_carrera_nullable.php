<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los grados de Básico no tienen carrera (esa noción solo aplica a
     * partir de Diversificado) — un pensum debe poder definirse solo por
     * curso+grado, sin carrera, para que la inscripción por grado funcione
     * también para esos alumnos.
     */
    public function up(): void
    {
        Schema::table('pensum', function (Blueprint $table) {
            $table->foreignId('id_carrera')->nullable()->change();
        });

        // El unique original (id_carrera, id_curso, id_grado) no bloquea
        // duplicados cuando id_carrera es NULL (NULL nunca es igual a NULL
        // en SQL), así que se agrega un índice único parcial para ese caso.
        DB::statement(
            'CREATE UNIQUE INDEX pensum_sin_carrera_curso_grado_unique ON pensum (id_curso, id_grado) WHERE id_carrera IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS pensum_sin_carrera_curso_grado_unique');

        Schema::table('pensum', function (Blueprint $table) {
            $table->foreignId('id_carrera')->nullable(false)->change();
        });
    }
};
