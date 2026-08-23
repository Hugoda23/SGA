<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

        $query = Inscripcion::with('alumno', 'asignacion', 'asistencias', 'calificacionesFinales');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->whereHas('alumno', fn ($a) => $a->where('nombre', 'ilike', "%{$q}%")->orWhere('apellido', 'ilike', "%{$q}%"))
                    ->orWhereHas('asignacion.curso', fn ($c) => $c->where('nombre_curso', 'ilike', "%{$q}%"));
            });
        }

        return $query->paginate($perPage);
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
        return $inscripcion->load('alumno', 'asignacion', 'asistencias', 'calificacionesFinales', 'detalleCalificaciones');
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
