<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Inscripcion;
use App\Models\Asignacion;
use App\Services\InscripcionService;
use App\Services\NotificacionService;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 50), 1000));
        $q = trim((string) $request->query('q', ''));

        $query = Inscripcion::with('alumno', 'asignacion.curso', 'asignacion.seccion', 'asistencias', 'calificacionesFinales');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->whereHas('alumno', fn ($a) => $a->where('nombre', 'ilike', "%{$q}%")->orWhere('apellido', 'ilike', "%{$q}%"))
                    ->orWhereHas('asignacion.curso', fn ($c) => $c->where('nombre_curso', 'ilike', "%{$q}%"));
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * GET /v1/inscripciones/resumen-alumnos
     * Vista de registros agrupada por alumno: un renglón por alumno con su
     * carrera, grado y sección actuales, los cursos en los que quedó
     * inscrito (según sus inscripciones activas) y la fecha de la primera
     * inscripción.
     */
    public function resumenPorAlumno(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 50), 1000));
        $q = trim((string) $request->query('q', ''));

        $query = Alumno::whereHas('inscripciones', fn ($i) => $i->where('estado', 'activo'))
            ->with([
                'carrera',
                'grado',
                'seccion',
                'inscripciones' => fn ($i) => $i->where('estado', 'activo')->with('asignacion.curso'),
            ]);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'ilike', "%{$q}%")
                    ->orWhere('apellido', 'ilike', "%{$q}%")
                    ->orWhere('codigo_mineduc', 'ilike', "%{$q}%");
            });
        }

        $alumnos = $query->orderBy('apellido')->orderBy('nombre')->paginate($perPage);

        $alumnos->getCollection()->transform(fn (Alumno $alumno) => [
            'id_alumno' => $alumno->id_alumno,
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'codigo_mineduc' => $alumno->codigo_mineduc,
            'id_carrera' => $alumno->id_carrera,
            'carrera' => $alumno->carrera?->nombre_carrera,
            'id_grado_actual' => $alumno->id_grado_actual,
            'grado' => $alumno->grado?->nombre,
            'id_seccion_actual' => $alumno->id_seccion_actual,
            'seccion' => $alumno->seccion?->nombre,
            'cursos' => $alumno->inscripciones
                ->pluck('asignacion.curso.nombre_curso')
                ->filter()
                ->unique()
                ->values(),
            'fecha_inscripcion' => $alumno->inscripciones->pluck('fecha_inscripcion')->filter()->min(),
            'inscripciones' => $alumno->inscripciones->map(fn ($ins) => [
                'id_inscripcion' => $ins->id_inscripcion,
                'curso' => $ins->asignacion?->curso?->nombre_curso ?? 'Curso',
                'fecha_inscripcion' => $ins->fecha_inscripcion,
            ])->values(),
        ]);

        return $alumnos;
    }

    public function store(Request $request, InscripcionService $inscripcionService)
    {
        $validated = $request->validate([
            'id_alumno' => 'required|exists:alumno,id_alumno',
            'id_asignacion' => 'required|exists:asignacion,id_asignacion',
            'fecha_inscripcion' => 'nullable|date',
        ]);

        $errores = $inscripcionService->verificarReglas(
            $validated['id_alumno'],
            $validated['id_asignacion']
        );

        if (!empty($errores)) {
            return response()->json([
                'message' => 'No se pudo realizar la inscripción.',
                'errores' => $errores,
            ], 422);
        }

        $inscripcion = Inscripcion::create(array_merge($validated, ['estado' => 'activo']));

        $asignacion = Asignacion::with('curso')->find($validated['id_asignacion']);
        $cursoNombre = $asignacion?->curso?->nombre_curso ?? 'asignación académica';
        $idUsuarioAlumno = \App\Models\Alumno::find($validated['id_alumno'])?->id_usuario;

        NotificacionService::paraUsuario(
            $idUsuarioAlumno,
            "Te has inscrito en {$cursoNombre} para el periodo actual."
        );

        return response()->json($inscripcion, 201);
    }

    /**
     * POST /v1/inscripciones/por-grado
     * Inscribe al alumno en todos los cursos del pensum de la carrera
     * (opcional) y grado indicados (los deja como carrera/grado actuales
     * del alumno), creando (o reutilizando) una asignación "pendiente" de
     * catedrático por cada curso que todavía no tenga una.
     */
    public function porGrado(Request $request, InscripcionService $inscripcionService)
    {
        $validated = $request->validate([
            'id_alumno' => 'required|exists:alumno,id_alumno',
            'id_grado' => 'required|exists:grado,id_grado',
            'id_carrera' => 'nullable|exists:carrera,id_carrera',
            'id_periodo' => 'nullable|exists:periodo_academico,id_periodo',
            'id_seccion' => 'nullable|exists:seccion,id_seccion',
        ]);

        $alumno = Alumno::findOrFail($validated['id_alumno']);

        $resultado = $inscripcionService->inscribirPorGrado(
            $alumno,
            $validated['id_grado'],
            $validated['id_carrera'] ?? null,
            $validated['id_periodo'] ?? null,
            $validated['id_seccion'] ?? null
        );

        if (!empty($resultado['errores'])) {
            return response()->json([
                'message' => 'No se pudo realizar la inscripción por grado.',
                'errores' => $resultado['errores'],
            ], 422);
        }

        if ($resultado['inscripciones_creadas'] > 0) {
            $cursos = implode(', ', $resultado['cursos']);
            NotificacionService::paraUsuario(
                $alumno->id_usuario,
                "Has sido inscrito en {$resultado['inscripciones_creadas']} curso(s) de {$resultado['periodo']->nombre}: {$cursos}."
            );
        }

        $mensaje = "Inscripción por grado completada: {$resultado['inscripciones_creadas']} curso(s) nuevo(s), {$resultado['ya_inscrito']} ya estaban inscritos.";

        if ($resultado['choque_horario'] > 0) {
            $detalle = implode("\n- ", $resultado['cursos_choque']);
            $mensaje .= "\n\n{$resultado['choque_horario']} curso(s) NO se pudieron inscribir por choque de horario:\n- {$detalle}";
        }

        return response()->json([
            'message' => $mensaje,
            'inscripciones_creadas' => $resultado['inscripciones_creadas'],
            'ya_inscrito' => $resultado['ya_inscrito'],
            'choque_horario' => $resultado['choque_horario'],
            'cursos' => $resultado['cursos'],
            'cursos_choque' => $resultado['cursos_choque'],
        ], 201);
    }

    /**
     * POST /v1/inscripciones/{inscripcion}/retirar
     * Retira la inscripción (estado 'retirado'), conservando el historial.
     */
    public function retirar(Inscripcion $inscripcion)
    {
        $periodoCerrado = $inscripcion->asignacion->periodo?->estado === 'cerrado';

        if ($periodoCerrado) {
            return response()->json([
                'message' => 'No se puede retirar la inscripción: el periodo académico está cerrado.',
            ], 422);
        }

        if ($inscripcion->estado === 'retirado') {
            return response()->json([
                'message' => 'La inscripción ya está retirada.',
            ], 422);
        }

        $inscripcion->update([
            'estado' => 'retirado',
            'fecha_retiro' => now()->toDateString(),
        ]);

        return response()->json($inscripcion);
    }

    public function show(Inscripcion $inscripcion)
    {
        return $inscripcion->load('alumno', 'asignacion.curso', 'asignacion.seccion', 'asistencias', 'calificacionesFinales', 'detalleCalificaciones');
    }

    public function update(Request $request, Inscripcion $inscripcion)
    {
        $validated = $request->validate([
            'fecha_inscripcion' => 'nullable|date',
        ]);

        $inscripcion->update($validated);

        return response()->json($inscripcion);
    }

    public function destroy(Inscripcion $inscripcion)
    {
        $inscripcion->delete();

        return response()->json(null, 204);
    }
}
