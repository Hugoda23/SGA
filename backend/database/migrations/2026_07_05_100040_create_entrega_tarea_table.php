<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrega_tarea', function (Blueprint $table) {
            $table->id('id_entrega');
            $table->foreignId('id_tarea')->constrained('tarea', 'id_tarea')->onDelete('cascade');
            $table->foreignId('id_alumno')->constrained('alumno', 'id_alumno')->onDelete('cascade');
            $table->string('archivo', 255)->nullable();
            $table->string('nombre_original', 255)->nullable();
            $table->string('link', 500)->nullable();
            $table->string('estado', 20)->default('entregada');
            $table->timestamp('fecha_entrega')->nullable();
            $table->decimal('calificacion', 5, 2)->nullable();

            $table->index('id_tarea');
            $table->index('id_alumno');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrega_tarea');
    }
};
