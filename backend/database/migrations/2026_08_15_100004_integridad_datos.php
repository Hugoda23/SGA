<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calificacion_final', function (Blueprint $table) {
            $table->unique('id_inscripcion', 'calificacion_final_id_inscripcion_unique');
        });

        DB::statement("ALTER TABLE inscripcion ADD CONSTRAINT inscripcion_estado_check CHECK (estado IN ('activo', 'retirado'))");
        DB::statement("ALTER TABLE alumno ADD CONSTRAINT alumno_estado_academico_check CHECK (estado_academico IN ('activo', 'inactivo', 'egresado', 'retirado'))");
        DB::statement("ALTER TABLE periodo_academico ADD CONSTRAINT periodo_academico_estado_check CHECK (estado IN ('activo', 'inactivo', 'cerrado'))");

        DB::statement("CREATE UNIQUE INDEX inscripcion_activa_por_asignacion_unique ON inscripcion (id_alumno, id_asignacion) WHERE estado = 'activo'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS inscripcion_activa_por_asignacion_unique');
        DB::statement('ALTER TABLE periodo_academico DROP CONSTRAINT IF EXISTS periodo_academico_estado_check');
        DB::statement('ALTER TABLE alumno DROP CONSTRAINT IF EXISTS alumno_estado_academico_check');
        DB::statement('ALTER TABLE inscripcion DROP CONSTRAINT IF EXISTS inscripcion_estado_check');

        Schema::table('calificacion_final', function (Blueprint $table) {
            $table->dropUnique('calificacion_final_id_inscripcion_unique');
        });
    }
};
