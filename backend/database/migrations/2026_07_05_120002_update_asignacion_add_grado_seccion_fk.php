<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignacion', function (Blueprint $table) {
            // Agregar nuevas columnas FK
            $table->foreignId('id_grado')->nullable()->constrained('grado', 'id_grado')->after('id_periodo');
            $table->foreignId('id_seccion')->nullable()->constrained('seccion', 'id_seccion')->after('id_grado');

            // Eliminar columnas de texto antiguas
            $table->dropColumn(['grado', 'seccion']);
        });
    }

    public function down(): void
    {
        Schema::table('asignacion', function (Blueprint $table) {
            $table->dropForeign(['id_grado']);
            $table->dropForeign(['id_seccion']);
            $table->dropColumn(['id_grado', 'id_seccion']);

            // Restaurar columnas originales
            $table->string('grado', 50)->nullable();
            $table->string('seccion', 20)->nullable();
        });
    }
};
