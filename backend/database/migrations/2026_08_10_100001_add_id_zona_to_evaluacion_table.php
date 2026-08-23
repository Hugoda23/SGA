<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluacion', function (Blueprint $table) {
            $table->foreignId('id_zona')->nullable()->constrained('zona_evaluacion', 'id_zona')->onDelete('set null')->after('id_asignacion');
        });
    }

    public function down(): void
    {
        Schema::table('evaluacion', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_zona');
        });
    }
};
