<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivo', function (Blueprint $table) {
            $table->id('id_archivo');
            $table->string('nombre', 255);
            $table->string('ruta', 255);
            $table->string('tipo', 50)->nullable();
            $table->timestamp('fecha_subida')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivo');
    }
};
