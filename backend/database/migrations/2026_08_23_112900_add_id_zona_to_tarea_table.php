<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarea', function (Blueprint $table) {
            $table->foreignId('id_zona')
                ->nullable()
                ->after('puntos')
                ->constrained('zona_evaluacion', 'id_zona')
                ->nullOnDelete();
            $table->index('id_zona');
        });
    }

    public function down(): void
    {
        Schema::table('tarea', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_zona');
        });
    }
};
