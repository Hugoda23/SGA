<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * En PostgreSQL una FK constraint no crea índice automáticamente
 * (a diferencia de MySQL). Esta migración indexa las columnas FK que
 * quedaron sin índice real y no son la columna líder de una PK/unique
 * compuesta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario_rol', function (Blueprint $table) {
            $table->index('id_rol');
        });

        Schema::table('rol_permiso', function (Blueprint $table) {
            $table->index('id_permiso');
        });

        Schema::table('curso_carrera', function (Blueprint $table) {
            $table->index('id_carrera');
        });

        Schema::table('notificacion', function (Blueprint $table) {
            $table->index('id_usuario');
        });

        Schema::table('bitacora', function (Blueprint $table) {
            $table->index('id_usuario');
        });

        Schema::table('reporte_generado', function (Blueprint $table) {
            $table->index('id_usuario');
        });

        Schema::table('alumno', function (Blueprint $table) {
            $table->index('id_carrera');
            $table->index('id_grado_actual');
        });

        Schema::table('aula', function (Blueprint $table) {
            $table->index('id_edificio');
        });

        Schema::table('pensum', function (Blueprint $table) {
            $table->index('id_curso');
            $table->index('id_grado');
        });

        Schema::table('asignacion', function (Blueprint $table) {
            $table->index('id_catedratico');
            $table->index('id_curso');
            $table->index('id_aula');
            $table->index('id_periodo');
            $table->index('id_grado');
            $table->index('id_seccion');
        });

        Schema::table('tarea', function (Blueprint $table) {
            $table->index('id_asignacion');
            $table->index('id_unidad');
        });

        Schema::table('inscripcion', function (Blueprint $table) {
            $table->index('id_alumno');
            $table->index('id_asignacion');
        });

        Schema::table('horario_detalle', function (Blueprint $table) {
            $table->index('id_asignacion');
        });

        Schema::table('evaluacion', function (Blueprint $table) {
            $table->index('id_asignacion');
            $table->index('id_zona');
        });

        Schema::table('entrega_tarea', function (Blueprint $table) {
            $table->index('id_tarea');
            $table->index('id_alumno');
        });

        Schema::table('asistencia', function (Blueprint $table) {
            $table->index('id_inscripcion');
        });

        Schema::table('detalle_calificacion', function (Blueprint $table) {
            $table->index('id_evaluacion');
            $table->index('id_inscripcion');
        });

        Schema::table('unidad', function (Blueprint $table) {
            $table->index('id_asignacion');
        });

        Schema::table('material', function (Blueprint $table) {
            $table->index('id_asignacion');
            $table->index('id_unidad');
            $table->index('id_archivo');
        });

        Schema::table('anuncio', function (Blueprint $table) {
            $table->index('id_asignacion');
        });

        Schema::table('zona_evaluacion', function (Blueprint $table) {
            $table->index('id_asignacion');
        });
    }

    public function down(): void
    {
        Schema::table('usuario_rol', fn (Blueprint $table) => $table->dropIndex(['id_rol']));
        Schema::table('rol_permiso', fn (Blueprint $table) => $table->dropIndex(['id_permiso']));
        Schema::table('curso_carrera', fn (Blueprint $table) => $table->dropIndex(['id_carrera']));
        Schema::table('notificacion', fn (Blueprint $table) => $table->dropIndex(['id_usuario']));
        Schema::table('bitacora', fn (Blueprint $table) => $table->dropIndex(['id_usuario']));
        Schema::table('reporte_generado', fn (Blueprint $table) => $table->dropIndex(['id_usuario']));

        Schema::table('alumno', function (Blueprint $table) {
            $table->dropIndex(['id_carrera']);
            $table->dropIndex(['id_grado_actual']);
        });

        Schema::table('aula', fn (Blueprint $table) => $table->dropIndex(['id_edificio']));

        Schema::table('pensum', function (Blueprint $table) {
            $table->dropIndex(['id_curso']);
            $table->dropIndex(['id_grado']);
        });

        Schema::table('asignacion', function (Blueprint $table) {
            $table->dropIndex(['id_catedratico']);
            $table->dropIndex(['id_curso']);
            $table->dropIndex(['id_aula']);
            $table->dropIndex(['id_periodo']);
            $table->dropIndex(['id_grado']);
            $table->dropIndex(['id_seccion']);
        });

        Schema::table('tarea', function (Blueprint $table) {
            $table->dropIndex(['id_asignacion']);
            $table->dropIndex(['id_unidad']);
        });

        Schema::table('inscripcion', function (Blueprint $table) {
            $table->dropIndex(['id_alumno']);
            $table->dropIndex(['id_asignacion']);
        });

        Schema::table('horario_detalle', fn (Blueprint $table) => $table->dropIndex(['id_asignacion']));

        Schema::table('evaluacion', function (Blueprint $table) {
            $table->dropIndex(['id_asignacion']);
            $table->dropIndex(['id_zona']);
        });

        Schema::table('entrega_tarea', function (Blueprint $table) {
            $table->dropIndex(['id_tarea']);
            $table->dropIndex(['id_alumno']);
        });

        Schema::table('asistencia', fn (Blueprint $table) => $table->dropIndex(['id_inscripcion']));

        Schema::table('detalle_calificacion', function (Blueprint $table) {
            $table->dropIndex(['id_evaluacion']);
            $table->dropIndex(['id_inscripcion']);
        });

        Schema::table('unidad', fn (Blueprint $table) => $table->dropIndex(['id_asignacion']));

        Schema::table('material', function (Blueprint $table) {
            $table->dropIndex(['id_asignacion']);
            $table->dropIndex(['id_unidad']);
            $table->dropIndex(['id_archivo']);
        });

        Schema::table('anuncio', fn (Blueprint $table) => $table->dropIndex(['id_asignacion']));
        Schema::table('zona_evaluacion', fn (Blueprint $table) => $table->dropIndex(['id_asignacion']));
    }
};
