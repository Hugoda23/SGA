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
            $table->foreignId('id_grado')->nullable()->constrained('grado', 'id_grado');
            $table->foreignId('id_seccion')->nullable()->constrained('seccion', 'id_seccion');

            $table->index('id_catedratico');
            $table->index('id_curso');
            $table->index('id_aula');
            $table->index('id_periodo');
            $table->index('id_grado');
            $table->index('id_seccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion');
    }
};
