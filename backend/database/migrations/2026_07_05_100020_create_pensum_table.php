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
            $table->string('grado', 50)->nullable();
            $table->boolean('obligatorio')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pensum');
    }
};
