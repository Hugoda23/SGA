<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id('id_bitacora');
            $table->foreignId('id_usuario')->nullable()->constrained('usuario', 'id_usuario');
            $table->string('accion', 255);
            $table->string('tabla_afectada', 100)->nullable();
            $table->integer('id_registro')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamp('fecha_hora')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
