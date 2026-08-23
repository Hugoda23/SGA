<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aula', function (Blueprint $table) {
            $table->id('id_aula');
            $table->string('nombre_aula', 100);
            $table->integer('capacidad')->nullable();
            $table->foreignId('id_edificio')->nullable()->constrained('edificio', 'id_edificio')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aula');
    }
};
