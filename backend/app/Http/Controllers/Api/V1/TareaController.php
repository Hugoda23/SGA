<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tarea;
use App\Models\Inscripcion;
use App\Models\ZonaEvaluacion;
use App\Services\NotificacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 50), 1000));
        $q = trim((string) $request->query('q', ''));

        $query = Tarea::with('asignacion.curso', 'entregas');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('titulo', 'ilike', "%{$q}%")
                    ->orWhere('descripcion', 'ilike', "%{$q}%")
                    ->orWhereHas('asignacion.curso', fn ($c) => $c->where('nombre_curso', 'ilike', "%{$q}%"));
            });
        }

        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'puntos' => 'nullable|numeric|min:0|max:1000',
            'id_zona' => 'nullable|exists:zona_evaluacion,id_zona',
            'fecha_entrega' => 'nullable|string',
            'permitir_link' => 'nullable|boolean',
            'id_asignacion' => 'required|exists:asignacion,id_asignacion',
            'id_unidad' => 'nullable|exists:unidad,id_unidad',
        ]);

        if (!empty($validated['id_zona'])) {
            $error = $this->validarCapacidadZona($validated['id_zona'], $validated['id_asignacion'], $validated['puntos'] ?? null);
            if ($error) {
                return $error;
            }
        }

        if (!empty($validated['fecha_entrega'])) {
            $validated['fecha_entrega'] = Carbon::parse($validated['fecha_entrega'])->format('Y-m-d H:i:s');
        }

        $validated['permitir_link'] = !empty($validated['permitir_link']);

        $tarea = Tarea::create($validated);
        $tarea->load('asignacion.curso');

        // Notificar a todos los alumnos inscritos en la asignación
        $inscripciones = Inscripcion::where('id_asignacion', $tarea->id_asignacion)
            ->with('alumno.usuario')
            ->get();

        $usuarios = $inscripciones->pluck('alumno.usuario')->filter();

        if ($usuarios->isNotEmpty()) {
            $fecha = $tarea->fecha_entrega
                ? Carbon::parse($tarea->fecha_entrega)->format('d/m/Y H:i')
                : 'Sin fecha límite';

            $mensaje = "Nueva tarea: {$tarea->titulo} — Curso: {$tarea->asignacion->curso->nombre_curso} — Fecha límite: {$fecha}";

            NotificacionService::crearMultiple($usuarios->all(), $mensaje);
        }

        return response()->json($tarea->load('asignacion.curso'), 201);
    }

    public function show(Tarea $tarea)
    {
        return $tarea->load('asignacion.curso', 'entregas.alumno');
    }

    public function update(Request $request, Tarea $tarea)
    {
        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:200',
            'descripcion' => 'nullable|string',
            'puntos' => 'nullable|numeric|min:0|max:1000',
            'id_zona' => 'nullable|exists:zona_evaluacion,id_zona',
            'fecha_entrega' => 'nullable|string',
            'permitir_link' => 'nullable|boolean',
            'id_asignacion' => 'sometimes|exists:asignacion,id_asignacion',
            'id_unidad' => 'nullable|exists:unidad,id_unidad',
        ]);

        $idZona = array_key_exists('id_zona', $validated) ? $validated['id_zona'] : $tarea->id_zona;
        if (!empty($idZona)) {
            $puntos = array_key_exists('puntos', $validated) ? $validated['puntos'] : $tarea->puntos;
            $idAsignacion = $validated['id_asignacion'] ?? $tarea->id_asignacion;
            $error = $this->validarCapacidadZona($idZona, $idAsignacion, $puntos, $tarea->id_tarea);
            if ($error) {
                return $error;
            }
        }

        if (!empty($validated['fecha_entrega'])) {
            $validated['fecha_entrega'] = Carbon::parse($validated['fecha_entrega'])->format('Y-m-d H:i:s');
        }

        if (array_key_exists('permitir_link', $validated)) {
            $validated['permitir_link'] = !empty($validated['permitir_link']);
        }

        $tarea->update($validated);

        return response()->json($tarea);
    }

    /**
     * Verifica que la tarea quepa en el presupuesto de puntos de su zona de
     * evaluación (zona.puntos - lo que ya consumen sus evaluaciones y otras
     * tareas). Devuelve una respuesta 422 si no cabe, o null si es válida.
     */
    private function validarCapacidadZona($idZona, $idAsignacion, $puntosTarea, $idTareaExcluir = null)
    {
        $zona = ZonaEvaluacion::find($idZona);

        if (!$zona || $zona->id_asignacion != $idAsignacion) {
            return response()->json(['errors' => ['id_zona' => ['La zona no pertenece a esta asignación.']]], 422);
        }

        if ($puntosTarea === null) {
            return response()->json(['errors' => ['puntos' => ['Debes indicar los puntos de la tarea para asignarla a una zona.']]], 422);
        }

        $disponible = $zona->puntosDisponibles(null, $idTareaExcluir);

        if ((float) $puntosTarea > $disponible) {
            return response()->json([
                'message' => "La zona \"{$zona->nombre}\" ya no tiene puntos disponibles: quedan {$disponible} de {$zona->puntos} pts.",
                'errors' => ['puntos' => ["La zona \"{$zona->nombre}\" solo tiene {$disponible} pts disponibles."]],
            ], 422);
        }

        return null;
    }

    public function destroy(Tarea $tarea)
    {
        $tarea->delete();
        return response()->json(null, 204);
    }

    public function porAsignacion($id_asignacion)
    {
        return Tarea::where('id_asignacion', $id_asignacion)
            ->with('entregas', 'unidad')
            ->orderBy('id_tarea', 'desc')
            ->get()
            ->map(function ($t) {
                return [
                    'id_tarea' => $t->id_tarea,
                    'titulo' => $t->titulo,
                    'descripcion' => $t->descripcion,
                    'puntos' => $t->puntos,
                    'id_zona' => $t->id_zona,
                    'fecha_entrega' => $t->fecha_entrega,
                    'permitir_link' => $t->permitir_link,
                    'id_unidad' => $t->id_unidad,
                    'unidad' => $t->unidad ? [
                        'id_unidad' => $t->unidad->id_unidad,
                        'numero_semana' => $t->unidad->numero_semana,
                        'titulo' => $t->unidad->titulo,
                    ] : null,
                    'total_entregas' => $t->entregas->count(),
                    'total_alumnos' => $t->asignacion?->inscripciones()->count() ?? 0,
                ];
            });
    }
}
