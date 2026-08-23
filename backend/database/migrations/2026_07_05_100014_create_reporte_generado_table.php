<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporte_generado', function (Blueprint $table) {
            $table->id('id_reporte');
            $table->foreignId('id_usuario')->nullable()->constrained('usuario', 'id_usuario')->onDelete('cascade');
            $table->string('tipo_reporte', 100)->nullable();
            $table->timestamp('fecha_generacion')->useCurrent();
            $table->decimal('tiempo_generacion', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_generado');
    }
};
