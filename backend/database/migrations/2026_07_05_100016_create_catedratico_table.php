<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catedratico', function (Blueprint $table) {
            $table->id('id_catedratico');
            $table->foreignId('id_usuario')->unique()->constrained('usuario', 'id_usuario')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('codigo', 50)->unique()->nullable();
            $table->string('especialidad', 150)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catedratico');
    }
};
