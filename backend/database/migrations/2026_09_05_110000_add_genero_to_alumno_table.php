<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Género del alumno: masculino o femenino.
     *
     * A diferencia de nacionalidad, tipo_documento y numero_documento, la
     * columna queda nullable en la base aunque la aplicación la exija: el
     * género de una persona ya registrada no se puede rellenar con un valor
     * por defecto ni con un marcador visible tipo 'PENDIENTE' —el CHECK solo
     * admite dos valores— y escribir un género inventado en el expediente de
     * alguien es peor que dejarlo vacío. Los alumnos previos se ven con '-'
     * en el listado y el formulario obliga a elegirlo al editarlos.
     */
    public function up(): void
    {
        Schema::table('alumno', function (Blueprint $table) {
            $table->string('genero', 20)->nullable()->after('fecha_nacimiento');
        });

        DB::statement("ALTER TABLE alumno ADD CONSTRAINT alumno_genero_check CHECK (genero IN ('masculino', 'femenino'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE alumno DROP CONSTRAINT IF EXISTS alumno_genero_check');

        Schema::table('alumno', function (Blueprint $table) {
            $table->dropColumn('genero');
        });
    }
};
