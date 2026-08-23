<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripcion', function (Blueprint $table) {
            $table->id('id_inscripcion');
            $table->foreignId('id_alumno')->constrained('alumno', 'id_alumno')->onDelete('cascade');
            $table->foreignId('id_asignacion')->constrained('asignacion', 'id_asignacion')->onDelete('cascade');
            $table->date('fecha_inscripcion')->useCurrent();
            $table->string('estado', 20)->default('activo');
            $table->date('fecha_retiro')->nullable();

            $table->index('id_alumno');
            $table->index('id_asignacion');
            $table->index('estado');
        });

        DB::statement("ALTER TABLE inscripcion ADD CONSTRAINT inscripcion_estado_check CHECK (estado IN ('activo', 'retirado'))");
        DB::statement("CREATE UNIQUE INDEX inscripcion_activa_por_asignacion_unique ON inscripcion (id_alumno, id_asignacion) WHERE estado = 'activo'");
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripcion');
    }
};
