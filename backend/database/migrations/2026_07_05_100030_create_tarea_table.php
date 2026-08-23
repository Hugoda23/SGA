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
            $table->foreignId('id_asignacion')->constrained('asignacion', 'id_asignacion')->onDelete('cascade');
            $table->foreignId('id_unidad')->nullable()->constrained('unidad', 'id_unidad')->nullOnDelete();
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->decimal('puntos', 5, 2)->nullable();
            $table->foreignId('id_zona')->nullable()->constrained('zona_evaluacion', 'id_zona')->nullOnDelete();
            $table->dateTime('fecha_entrega')->nullable();
            $table->boolean('permitir_link')->default(false);

            $table->index('id_asignacion');
            $table->index('id_unidad');
            $table->index('id_zona');
            $table->index('fecha_entrega');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarea');
    }
};
