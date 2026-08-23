<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignacion', function (Blueprint $table) {
            $table->id('id_asignacion');
            $table->foreignId('id_catedratico')->constrained('catedratico', 'id_catedratico');
            $table->foreignId('id_curso')->constrained('curso', 'id_curso');
            $table->foreignId('id_aula')->constrained('aula', 'id_aula');
            $table->foreignId('id_periodo')->constrained('periodo_academico', 'id_periodo');
            $table->string('grado', 50)->nullable();
            $table->string('seccion', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion');
    }
};
