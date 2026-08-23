<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario_detalle', function (Blueprint $table) {
            $table->id('id_horario');
            $table->foreignId('id_asignacion')->constrained('asignacion', 'id_asignacion')->onDelete('cascade');
            $table->string('dia_semana', 20)->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_detalle');
    }
};
