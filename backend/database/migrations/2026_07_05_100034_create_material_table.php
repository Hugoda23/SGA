<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material', function (Blueprint $table) {
            $table->id('id_material');
            $table->foreignId('id_asignacion')->constrained('asignacion', 'id_asignacion')->onDelete('cascade');
            $table->foreignId('id_unidad')->nullable()->constrained('unidad', 'id_unidad')->nullOnDelete();
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->string('tipo', 20)->default('archivo');
            $table->foreignId('id_archivo')->nullable()->constrained('archivo', 'id_archivo')->nullOnDelete();
            $table->string('url', 500)->nullable();
            $table->timestamp('fecha_publicacion')->useCurrent();

            $table->index('id_asignacion');
            $table->index('id_unidad');
            $table->index('id_archivo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material');
    }
};
