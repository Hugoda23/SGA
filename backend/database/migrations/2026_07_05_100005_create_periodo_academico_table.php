<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodo_academico');
    }
};
