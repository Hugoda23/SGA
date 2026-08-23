<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificacion_final', function (Blueprint $table) {
            $table->id('id_calificacion');
            $table->foreignId('id_inscripcion')->constrained('inscripcion', 'id_inscripcion')->onDelete('cascade');
            $table->integer('unidad_academica')->nullable();
            $table->decimal('nota_final', 5, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificacion_final');
    }
};
