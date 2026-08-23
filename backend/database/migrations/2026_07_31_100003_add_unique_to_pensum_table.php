<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pensum', function (Blueprint $table) {
            $table->unique(['id_carrera', 'id_curso', 'id_grado'], 'pensum_carrera_curso_grado_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pensum', function (Blueprint $table) {
            $table->dropUnique('pensum_carrera_curso_grado_unique');
        });
    }
};
