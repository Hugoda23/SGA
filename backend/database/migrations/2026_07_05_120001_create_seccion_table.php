<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seccion', function (Blueprint $table) {
            $table->id('id_seccion');
            $table->string('nombre', 20); // e.g. 'A', 'B', 'C'
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seccion');
    }
};
