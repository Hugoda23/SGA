<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia', function (Blueprint $table) {
            $table->id('id_asistencia');
            $table->foreignId('id_inscripcion')->constrained('inscripcion', 'id_inscripcion')->onDelete('cascade');
            $table->date('fecha')->nullable();
            $table->string('estado', 50)->nullable();

            $table->index('id_inscripcion');
            $table->index('fecha');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia');
    }
};
