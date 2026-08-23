<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pensum', function (Blueprint $table) {
            $table->id('id_pensum');
            $table->foreignId('id_carrera')->constrained('carrera', 'id_carrera')->onDelete('cascade');
            $table->foreignId('id_curso')->constrained('curso', 'id_curso')->onDelete('cascade');
            $table->foreignId('id_grado')->nullable()->constrained('grado', 'id_grado')->onDelete('cascade');
            $table->boolean('obligatorio')->default(true);

            $table->index('id_curso');
            $table->index('id_grado');
            $table->unique(['id_carrera', 'id_curso', 'id_grado'], 'pensum_carrera_curso_grado_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pensum');
    }
};
