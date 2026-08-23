<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumno', function (Blueprint $table) {
            $table->foreignId('id_grado_actual')
                ->nullable()
                ->after('id_carrera')
                ->constrained('grado', 'id_grado')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('alumno', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_grado_actual');
        });
    }
};
