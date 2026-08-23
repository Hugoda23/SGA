<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grado', function (Blueprint $table) {
            $table->id('id_grado');
            $table->string('nombre', 50);
            $table->string('nivel', 50)->nullable(); // e.g. 'Primaria', 'Básico', 'Diversificado'
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grado');
    }
};
