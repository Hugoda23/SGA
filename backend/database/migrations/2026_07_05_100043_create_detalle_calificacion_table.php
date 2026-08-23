<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_calificacion', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->foreignId('id_evaluacion')->constrained('evaluacion', 'id_evaluacion')->onDelete('cascade');
            $table->foreignId('id_inscripcion')->constrained('inscripcion', 'id_inscripcion')->onDelete('cascade');
            $table->decimal('nota', 5, 2)->nullable();
            $table->timestamps();

            $table->index('id_evaluacion');
            $table->index('id_inscripcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_calificacion');
    }
};
