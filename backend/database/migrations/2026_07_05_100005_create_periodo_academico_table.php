<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodo_academico', function (Blueprint $table) {
            $table->id('id_periodo');
            $table->string('nombre', 100);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 50)->nullable();

            $table->index('estado');
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
        });

        DB::statement("ALTER TABLE periodo_academico ADD CONSTRAINT periodo_academico_estado_check CHECK (estado IN ('activo', 'inactivo', 'cerrado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('periodo_academico');
    }
};
