<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrega_tarea', function (Blueprint $table) {
            $table->string('link', 500)->nullable()->after('nombre_original');
        });
    }

    public function down(): void
    {
        Schema::table('entrega_tarea', function (Blueprint $table) {
            $table->dropColumn('link');
        });
    }
};
