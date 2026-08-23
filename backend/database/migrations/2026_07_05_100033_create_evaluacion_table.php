<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluacion', function (Blueprint $table) {
            $table->id('id_evaluacion');
            $table->foreignId('id_asignacion')->constrained('asignacion', 'id_asignacion')->onDelete('cascade');
            $table->integer('unidad_academica')->nullable();
            $table->string('nombre', 100)->nullable();
            $table->decimal('porcentaje', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion');
    }
};
