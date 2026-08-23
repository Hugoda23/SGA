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
            $table->foreignId('id_zona')->nullable()->constrained('zona_evaluacion', 'id_zona')->onDelete('set null');
            $table->integer('unidad_academica')->nullable();
            $table->string('nombre', 100)->nullable();
            $table->decimal('porcentaje', 5, 2)->nullable();

            $table->index('id_asignacion');
            $table->index('id_zona');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion');
    }
};
