<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarea', function (Blueprint $table) {
            $table->dateTime('fecha_entrega')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tarea', function (Blueprint $table) {
            $table->date('fecha_entrega')->nullable()->change();
        });
    }
};
