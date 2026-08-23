<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zona_evaluacion', function (Blueprint $table) {
            $table->id('id_zona');
            $table->foreignId('id_asignacion')->constrained('asignacion', 'id_asignacion')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->decimal('puntos', 5, 2)->default(0);
            $table->integer('posicion')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zona_evaluacion');
    }
};
