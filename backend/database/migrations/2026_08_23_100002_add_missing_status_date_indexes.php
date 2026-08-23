<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columnas de estado/fecha usadas con frecuencia para filtrar
 * (dashboards, listados, reportes) que no tenían índice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->index('estado');
        });

        Schema::table('periodo_academico', function (Blueprint $table) {
            $table->index('estado');
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
        });

        Schema::table('bitacora', function (Blueprint $table) {
            $table->index('fecha_hora');
        });

        Schema::table('alumno', function (Blueprint $table) {
            $table->index('estado_academico');
        });

        Schema::table('inscripcion', function (Blueprint $table) {
            $table->index('estado');
        });

        Schema::table('unidad', function (Blueprint $table) {
            $table->index('estado');
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
        });

        Schema::table('tarea', function (Blueprint $table) {
            $table->index('fecha_entrega');
        });

        Schema::table('asistencia', function (Blueprint $table) {
            $table->index('fecha');
            $table->index('estado');
        });

        Schema::table('reporte_generado', function (Blueprint $table) {
            $table->index('fecha_generacion');
        });
    }

    public function down(): void
    {
        Schema::table('usuario', fn (Blueprint $table) => $table->dropIndex(['estado']));

        Schema::table('periodo_academico', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['fecha_inicio']);
            $table->dropIndex(['fecha_fin']);
        });

        Schema::table('bitacora', fn (Blueprint $table) => $table->dropIndex(['fecha_hora']));
        Schema::table('alumno', fn (Blueprint $table) => $table->dropIndex(['estado_academico']));
        Schema::table('inscripcion', fn (Blueprint $table) => $table->dropIndex(['estado']));

        Schema::table('unidad', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['fecha_inicio']);
            $table->dropIndex(['fecha_fin']);
        });

        Schema::table('tarea', fn (Blueprint $table) => $table->dropIndex(['fecha_entrega']));

        Schema::table('asistencia', function (Blueprint $table) {
            $table->dropIndex(['fecha']);
            $table->dropIndex(['estado']);
        });

        Schema::table('reporte_generado', fn (Blueprint $table) => $table->dropIndex(['fecha_generacion']));
    }
};
