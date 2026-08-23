<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumno', function (Blueprint $table) {
            $table->id('id_alumno');
            $table->foreignId('id_usuario')->unique()->constrained('usuario', 'id_usuario')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('codigo_mineduc', 50)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->foreignId('id_carrera')->nullable()->constrained('carrera', 'id_carrera');
            $table->foreignId('id_grado_actual')->nullable()->constrained('grado', 'id_grado')->nullOnDelete();
            $table->string('estado_academico', 20)->default('activo');

            $table->index('id_carrera');
            $table->index('id_grado_actual');
            $table->index('estado_academico');
        });

        DB::statement("ALTER TABLE alumno ADD CONSTRAINT alumno_estado_academico_check CHECK (estado_academico IN ('activo', 'inactivo', 'egresado', 'retirado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('alumno');
    }
};
