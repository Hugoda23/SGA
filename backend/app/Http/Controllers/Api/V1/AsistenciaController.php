<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\Inscripcion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
    public function index()
    {
        return Asistencia::with('inscripcion')->get();
    }

    public function porAsignacion(Request $request, $id_asignacion)
    {
        $this->verificarCatedratico($request, $id_asignacion);

        $asignacion = Asignacion::with('inscripciones.alumno')->findOrFail($id_asignacion);
        $inscripciones = $asignacion->inscripciones;

        $todas = Asistencia::whereIn('id_inscripcion', $inscripciones->pluck('id_inscripcion'))
            ->orderBy('fecha', 'desc')
            ->get()
            ->groupBy('fecha');

        return response()->json([
            'asignacion' => [
                'id_asignacion' => $asignacion->id_asignacion,
                'curso' => $asignacion->curso?->nombre_curso,
            ],
            'inscripciones' => $inscripciones->map(fn($i) => [
                'id_inscripcion' => $i->id_inscripcion,
                'id_alumno' => $i->alumno?->id_alumno,
                'alumno_nombre' => $i->alumno ? "{$i->alumno->nombre} {$i->alumno->apellido}" : '—',
            ]),
            'asistencias_por_fecha' => $todas->map(fn($items, $fecha) => [
                'fecha' => $fecha,
                'registros' => $items->map(fn($a) => [
                    'id_asistencia' => $a->id_asistencia,
                    'id_inscripcion' => $a->id_inscripcion,
                    'estado' => $a->estado,
                ]),
            ])->values(),
        ]);
    }

    public function guardarMasivo(Request $request)
    {
        $validated = $request->validate([
            'id_asignacion' => 'required|exists:asignacion,id_asignacion',
            'fecha' => 'required|date',
            'asistencias' => 'required|array',
            'asistencias.*.id_inscripcion' => 'required|exists:inscripcion,id_inscripcion',
            'asistencias.*.estado' => 'nullable|string|max:50',
        ]);

        $this->verificarCatedratico($request, $validated['id_asignacion']);

        $fecha = $validated['fecha'];

        DB::transaction(function () use ($validated, $fecha) {
            foreach ($validated['asistencias'] as $item) {
                Asistencia::updateOrCreate(
                    [
                        'id_inscripcion' => $item['id_inscripcion'],
                        'fecha' => $fecha,
                    ],
                    ['estado' => $item['estado']]
                );
            }
        });

        return response()->json(['message' => 'Asistencias guardadas correctamente.']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_inscripcion' => 'required|exists:inscripcion,id_inscripcion',
            'fecha' => 'nullable|date',
            'estado' => 'nullable|string|max:50',
        ]);

        $asistencia = Asistencia::create($validated);
        return response()->json($asistencia, 201);
    }

    public function show(Asistencia $asistencia)
    {
        return $asistencia->load('inscripcion');
    }

    public function update(Request $request, Asistencia $asistencia)
    {
        $validated = $request->validate([
            'fecha' => 'nullable|date',
            'estado' => 'nullable|string|max:50',
        ]);

        $asistencia->update($validated);
        return response()->json($asistencia);
    }

    public function destroy(Asistencia $asistencia)
    {
        $asistencia->delete();
        return response()->json(null, 204);
    }

    /**
     * Verifica que el catedrático autenticado sea dueño de la asignación.
     * Si el usuario no tiene perfil de catedrático (ej. admin) se permite el acceso.
     */
    private function verificarCatedratico(Request $request, $id_asignacion)
    {
        $usuario = $request->user();
        $catedratico = Catedratico::where('id_usuario', $usuario->id_usuario)->first();

        if ($catedratico) {
            $asignacion = Asignacion::find($id_asignacion);
            if (!$asignacion || $asignacion->id_catedratico !== $catedratico->id_catedratico) {
                return response()->json(['error' => 'No autorizado para este curso'], 403)->throwResponse();
            }
        }
    }
}
