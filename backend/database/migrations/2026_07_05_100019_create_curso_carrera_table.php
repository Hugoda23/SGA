<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_curso')->constrained('curso', 'id_curso')->onDelete('cascade');
            $table->foreignId('id_carrera')->constrained('carrera', 'id_carrera')->onDelete('cascade');
            $table->unique(['id_curso', 'id_carrera']);

            $table->index('id_carrera');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_carrera');
    }
};
