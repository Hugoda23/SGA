<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curso', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_carrera');
        });
    }

    public function down(): void
    {
        Schema::table('curso', function (Blueprint $table) {
            $table->foreignId('id_carrera')->nullable()->after('descripcion')->constrained('carrera', 'id_carrera')->onDelete('cascade');
        });
    }
};
