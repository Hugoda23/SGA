<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripcion');
    }
};
