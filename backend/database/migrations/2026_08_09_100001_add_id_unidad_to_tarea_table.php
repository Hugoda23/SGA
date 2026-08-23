<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarea', function (Blueprint $table) {
            $table->foreignId('id_unidad')->nullable()->after('id_asignacion')->constrained('unidad', 'id_unidad')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tarea', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_unidad');
        });
    }
};
