<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alumno', function (Blueprint $table) {
            $table->foreignId('id_seccion_actual')->nullable()->after('id_grado_actual')
                ->constrained('seccion', 'id_seccion')->nullOnDelete();
            $table->index('id_seccion_actual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumno', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_seccion_actual');
        });
    }
};
