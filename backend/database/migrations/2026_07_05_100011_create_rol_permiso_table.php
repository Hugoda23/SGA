<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->foreignId('id_rol')->constrained('rol', 'id_rol')->onDelete('cascade');
            $table->foreignId('id_permiso')->constrained('permiso', 'id_permiso')->onDelete('cascade');
            $table->primary(['id_rol', 'id_permiso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_permiso');
    }
};
