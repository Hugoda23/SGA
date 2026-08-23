<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edificio', function (Blueprint $table) {
            $table->id('id_edificio');
            $table->string('nombre', 100);
            $table->string('ubicacion', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edificio');
    }
};
