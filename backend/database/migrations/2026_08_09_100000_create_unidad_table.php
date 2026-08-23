<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidad', function (Blueprint $table) {
            $table->id('id_unidad');
            $table->foreignId('id_asignacion')->constrained('asignacion', 'id_asignacion')->onDelete('cascade');
            $table->integer('numero_semana')->nullable();
            $table->string('titulo', 200);
            $table->text('temas')->nullable();
            $table->text('competencia')->nullable();
            $table->string('estado', 30)->default('planificado');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidad');
    }
};
