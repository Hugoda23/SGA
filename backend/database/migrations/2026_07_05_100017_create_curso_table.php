<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso', function (Blueprint $table) {
            $table->id('id_curso');
            $table->string('nombre_curso', 150);
            $table->text('descripcion')->nullable();
            $table->foreignId('id_carrera')->nullable()->constrained('carrera', 'id_carrera')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso');
    }
};
