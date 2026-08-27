<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AlumnoController;
use App\Http\Controllers\Api\V1\ArchivoController;
use App\Http\Controllers\Api\V1\AsignacionController;
use App\Http\Controllers\Api\V1\AsistenciaController;
use App\Http\Controllers\Api\V1\AulaController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BitacoraController;
use App\Http\Controllers\Api\V1\CalificacionFinalController;
use App\Http\Controllers\Api\V1\CarreraController;
use App\Http\Controllers\Api\V1\CatedraticoController;
use App\Http\Controllers\Api\V1\ConfiguracionController;
use App\Http\Controllers\Api\V1\CursoController;
use App\Http\Controllers\Api\V1\DetalleCalificacionController;
use App\Http\Controllers\Api\V1\EdificioController;
use App\Http\Controllers\Api\V1\EntregaTareaController;
use App\Http\Controllers\Api\V1\EvaluacionController;
use App\Http\Controllers\Api\V1\HorarioDetalleController;
use App\Http\Controllers\Api\V1\InscripcionController;
use App\Http\Controllers\Api\V1\NotificacionController;
use App\Http\Controllers\Api\V1\PushSubscriptionController;
use App\Http\Controllers\Api\V1\PensumController;
use App\Http\Controllers\Api\V1\PeriodoAcademicoController;
use App\Http\Controllers\Api\V1\PermisoController;
use App\Http\Controllers\Api\V1\ReporteGeneradoController;
use App\Http\Controllers\Api\V1\RolController;
use App\Http\Controllers\Api\V1\TareaController;
use App\Http\Controllers\Api\V1\UserManagementController;
use App\Http\Controllers\Api\V1\UsuarioController;
use App\Http\Controllers\Api\V1\PdfReportController;
use App\Http\Controllers\Api\V1\GradoController;
use App\Http\Controllers\Api\V1\SeccionController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\SistemaLogController;
use App\Http\Controllers\Api\V1\MisCursosController;
use App\Http\Controllers\Api\V1\MisTareasController;
use App\Http\Controllers\Api\V1\RegistroCalificacionesController;
use App\Http\Controllers\Api\V1\UnidadController;
use App\Http\Controllers\Api\V1\ConfiguracionCursoController;
use App\Http\Controllers\Api\V1\MaterialController;
use App\Http\Controllers\Api\V1\AnuncioController;
use App\Http\Controllers\Api\V1\AlumnoCursoController;
use App\Http\Controllers\Api\V1\ZonaEvaluacionController;

Route::prefix('v1')->group(function () {
    Route::get('sistema/estado',       [ConfiguracionController::class, 'estadoPublico']);
    Route::post('auth/login',          [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/logout',         [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('auth/me',              [AuthController::class, 'me'])->middleware(['auth:sanctum', 'refresh.token']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum', 'mantenimiento', 'refresh.token', 'throttle:api'])->group(function () {
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('catedratico/mis-cursos', [MisCursosController::class, 'index']);
        Route::get('catedratico/horario', [MisCursosController::class, 'horario']);
        Route::get('catedratico/tareas-por-curso', [MisCursosController::class, 'tareasPorCurso']);

        // Registro de Calificaciones
        Route::get('registro-calificaciones/{id_asignacion}', [RegistroCalificacionesController::class, 'show']);
        Route::post('registro-calificaciones/{id_asignacion}/guardar', [RegistroCalificacionesController::class, 'guardar']);
        Route::post('registro-calificaciones/{id_asignacion}/evaluaciones', [RegistroCalificacionesController::class, 'crearEvaluacion']);
        Route::delete('registro-calificaciones/evaluaciones/{id_evaluacion}', [RegistroCalificacionesController::class, 'eliminarEvaluacion']);

        // Gestión de usuarios staff
        Route::post('usuarios/admin',       [UserManagementController::class, 'store'])->middleware('permiso:usuarios');
        Route::get('usuarios/admin',        [UserManagementController::class, 'index'])->middleware('permiso:usuarios');
        Route::put('usuarios/{usuario}/password', [UserManagementController::class, 'updatePassword'])->middleware('permiso:usuarios');
        Route::patch('usuarios/{usuario}/estado', [UserManagementController::class, 'toggleStatus'])->middleware('permiso:usuarios');

        // Notificaciones - rutas específicas de usuario
        Route::get('notificaciones/mias', [NotificacionController::class, 'misNotificaciones']);
        Route::get('notificaciones/no-leidas', [NotificacionController::class, 'noLeidas']);
        Route::patch('notificaciones/{notificacion}/leido', [NotificacionController::class, 'marcarLeido']);
        Route::post('notificaciones/marcar-todas-leidas', [NotificacionController::class, 'marcarTodasLeidas']);

        // Suscripción a notificaciones push del navegador (Web Push)
        Route::post('push/subscribe', [PushSubscriptionController::class, 'store']);
        Route::post('push/unsubscribe', [PushSubscriptionController::class, 'destroy']);

        // Reportes PDF
        Route::get('reportes/pdf/boletin/{id}', [PdfReportController::class, 'downloadBoletin']);
        Route::get('reportes/pdf/kardex/{id}',  [PdfReportController::class, 'downloadKardex']);
        Route::get('reportes/pdf/acta/{id_asignacion}', [PdfReportController::class, 'downloadActa']);
        Route::get('reportes/pdf/bitacora',     [PdfReportController::class, 'downloadBitacora'])->middleware('permiso:bitacoras');
        Route::get('reportes/pdf/constancia/{id}', [PdfReportController::class, 'downloadConstancia']);
        Route::get('reportes/pdf/asistencia/{id_asignacion}', [PdfReportController::class, 'downloadAsistencia']);
        Route::get('reportes/pdf/asistencia-final/{id_asignacion}', [PdfReportController::class, 'downloadAsistenciaFinal']);
        Route::get('reportes/pdf/listado-alumnos/{id_asignacion}', [PdfReportController::class, 'downloadListadoAlumnos']);
        Route::get('reportes/rendimiento', [PdfReportController::class, 'rendimientoPorPeriodo'])->middleware('permiso:reportes');

        // Archivos - upload
        Route::post('archivos/upload', [ArchivoController::class, 'upload'])->middleware('permiso:archivos');
        Route::get('archivos/{archivo}/descargar', [ArchivoController::class, 'descargar'])->middleware('permiso:archivos');

        // Asistencia - batch por asignación
        Route::get('asistencias/por-asignacion/{id_asignacion}', [AsistenciaController::class, 'porAsignacion']);
        Route::post('asistencias/guardar-masivo', [AsistenciaController::class, 'guardarMasivo']);

        // Mis Tareas (alumno)
        Route::get('mis-tareas', [MisTareasController::class, 'index']);

        // Tareas por asignación (profesor en ConfiguracionCurso)
        Route::get('tareas/por-asignacion/{id_asignacion}', [TareaController::class, 'porAsignacion']);

        // Avance programático (módulos por semana)
        Route::get('unidades/por-asignacion/{id_asignacion}', [UnidadController::class, 'porAsignacion']);
        Route::post('unidades', [UnidadController::class, 'store']);
        Route::get('unidades/{id_unidad}', [UnidadController::class, 'show']);
        Route::put('unidades/{id_unidad}', [UnidadController::class, 'update']);
        Route::patch('unidades/{id_unidad}', [UnidadController::class, 'update']);
        Route::delete('unidades/{id_unidad}', [UnidadController::class, 'destroy']);

        // Configuración del curso (catedrático)
        Route::get('catedratico/configuracion-curso/{id_asignacion}', [ConfiguracionCursoController::class, 'show']);

        // Zonas de evaluación (estructura de 100 puntos)
        Route::get('catedratico/configuracion-curso/{id_asignacion}/zonas', [ZonaEvaluacionController::class, 'porAsignacion']);
        Route::post('zonas', [ZonaEvaluacionController::class, 'store']);
        Route::put('zonas/{id_zona}', [ZonaEvaluacionController::class, 'update']);
        Route::patch('zonas/{id_zona}', [ZonaEvaluacionController::class, 'update']);
        Route::delete('zonas/{id_zona}', [ZonaEvaluacionController::class, 'destroy']);

        // Materiales del curso
        Route::get('materiales/por-asignacion/{id_asignacion}', [MaterialController::class, 'porAsignacion']);
        Route::post('materiales', [MaterialController::class, 'store']);
        Route::put('materiales/{id_material}', [MaterialController::class, 'update']);
        Route::patch('materiales/{id_material}', [MaterialController::class, 'update']);
        Route::delete('materiales/{id_material}', [MaterialController::class, 'destroy']);

        // Anuncios del curso
        Route::get('anuncios/por-asignacion/{id_asignacion}', [AnuncioController::class, 'porAsignacion']);
        Route::post('anuncios', [AnuncioController::class, 'store']);
        Route::put('anuncios/{id_anuncio}', [AnuncioController::class, 'update']);
        Route::patch('anuncios/{id_anuncio}', [AnuncioController::class, 'update']);
        Route::delete('anuncios/{id_anuncio}', [AnuncioController::class, 'destroy']);

        // Vista alumno
        Route::get('alumno/resumen', [AlumnoCursoController::class, 'resumen']);
        Route::get('alumno/horario', [AlumnoCursoController::class, 'horario']);
        Route::get('alumno/mis-cursos', [AlumnoCursoController::class, 'misCursos']);
        Route::get('alumno/curso/{id_asignacion}', [AlumnoCursoController::class, 'show']);
        Route::post('alumno/curso/{id_asignacion}/entregar/{id_tarea}', [AlumnoCursoController::class, 'entregarTarea']);

        // Reporte de avance programático
        Route::get('reportes/pdf/avance-programatico/{id_asignacion}', [PdfReportController::class, 'downloadAvanceProgramatico']);

        // Entregas de tarea (profesor + alumno)
        Route::get('entregas-tarea/por-tarea/{id_tarea}', [EntregaTareaController::class, 'porTarea'])->middleware('permiso:entregas.ver');
        Route::post('entregas-tarea/calificar/{id_entrega}', [EntregaTareaController::class, 'calificar'])->middleware('permiso:entregas.calificar');
        Route::post('entregas-tarea/subir-archivo', [EntregaTareaController::class, 'subirArchivo'])->middleware('permiso:entregas.subir');
        Route::post('entregas-tarea/presentar/{id_entrega}', [EntregaTareaController::class, 'presentar'])->middleware('permiso:entregas.subir');

        // API Resources
        Route::apiResource('usuarios',               UsuarioController::class)->middleware('permiso:usuarios');
        Route::get('usuarios/{usuario}/permisos',    [UsuarioController::class, 'permisos'])->middleware('permiso:permisos');
        Route::put('usuarios/{usuario}/permisos',    [UsuarioController::class, 'syncPermisosPropios'])->middleware('permiso:permisos');
        Route::apiResource('roles',                  RolController::class)->parameters(['roles' => 'rol'])->middleware('permiso:roles');
        Route::post('roles/{rol}/permisos',          [RolController::class, 'syncPermisos'])->middleware('permiso:roles');
        Route::post('permisos/seed',                 [PermisoController::class, 'seedDefaults'])->middleware('permiso:permisos');
        Route::apiResource('permisos',               PermisoController::class)->middleware('permiso:permisos');
        Route::apiResource('edificios',              EdificioController::class)->middleware('permiso:edificios');
        Route::apiResource('carreras',               CarreraController::class)->middleware('permiso:carreras');
        Route::apiResource('periodos-academicos',    PeriodoAcademicoController::class)->parameters(['periodos-academicos' => 'periodo_academico'])->middleware('permiso:periodos');
        Route::post('periodos-academicos/{periodo_academico}/cerrar', [PeriodoAcademicoController::class, 'cerrar'])->middleware('permiso:periodos');
        Route::get('configuraciones',                 [ConfiguracionController::class, 'index'])->middleware('permiso:configuracion');
        Route::put('configuraciones',                 [ConfiguracionController::class, 'update'])->middleware('permiso:configuracion');
        Route::apiResource('archivos',               ArchivoController::class)->middleware('permiso:archivos');
        Route::apiResource('notificaciones',          NotificacionController::class)->parameters(['notificaciones' => 'notificacion'])->middleware('permiso:notificaciones');
        Route::apiResource('bitacoras',              BitacoraController::class)->middleware('permiso:bitacoras');
        Route::get('logs', [SistemaLogController::class, 'index'])->middleware('permiso:logs');
        Route::delete('logs', [SistemaLogController::class, 'destroy'])->middleware('permiso:logs');
        Route::apiResource('reportes-generados',     ReporteGeneradoController::class)->parameters(['reportes-generados' => 'reporte_generado'])->middleware('permiso:reportes');
        Route::apiResource('alumnos',                AlumnoController::class)->middleware('permiso:alumnos');
        Route::apiResource('catedraticos',           CatedraticoController::class)->middleware('permiso:catedraticos');
        Route::apiResource('cursos',                 CursoController::class)->middleware('permiso:cursos');
        Route::apiResource('aulas',                  AulaController::class)->middleware('permiso:aulas');
        Route::apiResource('grados',                 GradoController::class)->middleware('permiso:grados');
        Route::apiResource('secciones',              SeccionController::class)->parameters(['secciones' => 'seccion'])->middleware('permiso:secciones');
        Route::apiResource('pensums',                PensumController::class)->middleware('permiso:pensum');
        Route::apiResource('asignaciones',           AsignacionController::class)->parameters(['asignaciones' => 'asignacion'])->middleware('permiso:asignaciones');
        Route::apiResource('tareas',                 TareaController::class)->middleware('permiso:tareas');
        Route::get('inscripciones/resumen-alumnos', [InscripcionController::class, 'resumenPorAlumno'])->middleware('permiso:inscripciones');
        Route::apiResource('inscripciones',          InscripcionController::class)->parameters(['inscripciones' => 'inscripcion'])->middleware('permiso:inscripciones');
        Route::post('inscripciones/{inscripcion}/retirar', [InscripcionController::class, 'retirar'])->middleware('permiso:inscripciones');
        Route::post('inscripciones/por-grado', [InscripcionController::class, 'porGrado'])->middleware('permiso:inscripciones');
        Route::apiResource('horarios',               HorarioDetalleController::class)->parameters(['horarios' => 'horario_detalle'])->middleware('permiso:horarios');
        Route::apiResource('evaluaciones',           EvaluacionController::class)->parameters(['evaluaciones' => 'evaluacion'])->middleware('permiso:evaluaciones');
        Route::apiResource('entregas-tarea',         EntregaTareaController::class)->parameters(['entregas-tarea' => 'entrega_tarea'])->middleware('permiso:entregas');
        Route::apiResource('asistencias',            AsistenciaController::class)->middleware('permiso:asistencias');
        Route::apiResource('calificaciones-finales', CalificacionFinalController::class)->parameters(['calificaciones-finales' => 'calificacion_final'])->middleware('permiso:calificaciones');
        Route::apiResource('detalles-calificacion',  DetalleCalificacionController::class)->parameters(['detalles-calificacion' => 'detalle_calificacion'])->middleware('permiso:calificaciones');
    });
});
