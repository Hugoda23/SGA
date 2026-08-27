<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite crear una asignación "pendiente" (curso ya decidido para un
     * grado/periodo, catedrático y aula aún sin asignar) para soportar la
     * inscripción masiva por grado a partir del pensum.
     */
    public function up(): void
    {
        Schema::table('asignacion', function (Blueprint $table) {
            $table->foreignId('id_catedratico')->nullable()->change();
            $table->foreignId('id_aula')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('asignacion', function (Blueprint $table) {
            $table->foreignId('id_catedratico')->nullable(false)->change();
            $table->foreignId('id_aula')->nullable(false)->change();
        });
    }
};
