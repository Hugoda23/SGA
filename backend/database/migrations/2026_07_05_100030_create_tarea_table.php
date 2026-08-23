<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarea', function (Blueprint $table) {
            $table->id('id_tarea');
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->foreignId('id_asignacion')->constrained('asignacion', 'id_asignacion')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarea');
    }
};
