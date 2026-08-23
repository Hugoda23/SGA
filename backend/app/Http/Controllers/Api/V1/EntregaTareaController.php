<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EntregaTarea;
use App\Models\Tarea;
use App\Services\NotificacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EntregaTareaController extends Controller
{
    public function index()
    {
        return EntregaTarea::with('tarea', 'alumno')->get();
    }

    public function store(Request $request)
    {
        $maxCalificacion = $this->puntosMaximos($request->input('id_tarea'));

        $validated = $request->validate([
            'id_tarea' => 'required|exists:tarea,id_tarea',
            'id_alumno' => 'required|exists:alumno,id_alumno',
            'archivo' => 'nullable|string|max:255',
            'link' => 'nullable|url|max:500',
            'calificacion' => "nullable|numeric|min:0|max:{$maxCalificacion}",
        ]);

        $entrega = EntregaTarea::create($validated);

        return response()->json($entrega, 201);
    }

    public function show(EntregaTarea $entregaTarea)
    {
        return $entregaTarea->load('tarea', 'alumno');
    }

    public function update(Request $request, EntregaTarea $entregaTarea)
    {
        $maxCalificacion = $this->puntosMaximos($entregaTarea->id_tarea);

        $validated = $request->validate([
            'archivo' => 'nullable|string|max:255',
            'link' => 'nullable|url|max:500',
            'calificacion' => "nullable|numeric|min:0|max:{$maxCalificacion}",
        ]);

        $entregaTarea->update($validated);

        return response()->json($entregaTarea);
    }

    /**
     * Puntos máximos que puede valer una calificación de entrega: los
     * puntos configurados en la tarea, o 100 si la tarea no definió puntos
     * (compatibilidad con tareas creadas antes de este campo).
     */
    private function puntosMaximos($idTarea): float
    {
        $puntos = Tarea::find($idTarea)?->puntos;

        return $puntos !== null ? (float) $puntos : 100.0;
    }

    public function destroy(EntregaTarea $entregaTarea)
    {
        if ($entregaTarea->archivo) {
            Storage::disk('public')->delete($entregaTarea->archivo);
        }
        $entregaTarea->delete();
        return response()->json(null, 204);
    }

    // Entregas por tarea (profesor) — solo entregas presentadas
    public function porTarea($id_tarea)
    {
        $tarea = Tarea::with('asignacion.inscripciones.alumno')->findOrFail($id_tarea);
        $entregas = EntregaTarea::where('id_tarea', $id_tarea)->entregadas()->get()->keyBy('id_alumno');

        $alumnos = $tarea->asignacion->inscripciones->map(function ($ins) use ($entregas) {
            $entrega = $entregas->get($ins->id_alumno);
            return [
                'id_inscripcion' => $ins->id_inscripcion,
                'id_alumno' => $ins->id_alumno,
                'alumno_nombre' => $ins->alumno ? "{$ins->alumno->nombre} {$ins->alumno->apellido}" : '—',
                'entrega' => $entrega ? [
                    'id_entrega' => $entrega->id_entrega,
                    'archivo' => $entrega->archivo,
                    'nombre_original' => $entrega->nombre_original,
                    'link' => $entrega->link,
                    'fecha_entrega' => $entrega->fecha_entrega,
                    'calificacion' => $entrega->calificacion,
                ] : null,
            ];
        });

        return response()->json([
            'tarea' => ['id_tarea' => $tarea->id_tarea, 'titulo' => $tarea->titulo, 'puntos' => $tarea->puntos, 'fecha_entrega' => $tarea->fecha_entrega],
            'alumnos' => $alumnos,
        ]);
    }

    // Calificar entrega
    public function calificar(Request $request, $id_entrega)
    {
        $entrega = EntregaTarea::findOrFail($id_entrega);
        $maxCalificacion = $this->puntosMaximos($entrega->id_tarea);

        $validated = $request->validate([
            'calificacion' => "required|numeric|min:0|max:{$maxCalificacion}",
        ]);

        $entrega->update(['calificacion' => $validated['calificacion']]);
        return response()->json($entrega);
    }

    // Subir archivo o enlace de entrega (alumno) — queda en borrador hasta presentar
    // El id_alumno se deriva del token autenticado (nunca se confía en el cliente)
    public function subirArchivo(Request $request)
    {
        $request->validate([
            'id_tarea' => 'required|exists:tarea,id_tarea',
        ]);

        $alumno = $request->user()->alumno;
        if (!$alumno) {
            return response()->json(['error' => 'Solo los alumnos pueden subir entregas'], 403);
        }

        $tarea = Tarea::findOrFail($request->id_tarea);

        $estaInscrito = $alumno->inscripciones()
            ->where('id_asignacion', $tarea->id_asignacion)
            ->exists();
        if (!$estaInscrito) {
            return response()->json(['error' => 'No estás inscrito en este curso'], 403);
        }

        $datos = [];

        if ($request->hasFile('archivo')) {
            $request->validate([
                'archivo' => 'required|file|max:20480|mimetypes:application/pdf,application/zip,application/x-rar-compressed,application/vnd.rar,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.oasis.opendocument.text,text/plain,image/jpeg,image/png,image/gif',
            ]);

            $file = $request->file('archivo');
            $originalName = $file->getClientOriginalName();
            $path = $file->store('entregas', 'public');

            $datos = ['archivo' => $path, 'nombre_original' => $originalName, 'link' => null];
        } else {
            $request->validate([
                'link' => 'required|url|max:500',
            ]);

            if (!$tarea->permitir_link) {
                return response()->json(['error' => 'Esta tarea no acepta entregas por enlace'], 422);
            }

            $datos = ['link' => $request->link];
        }

        // La entrega queda como borrador: no es visible para el docente hasta presentarla
        $datos['estado'] = 'borrador';
        $datos['fecha_entrega'] = null;

        $entrega = EntregaTarea::updateOrCreate(
            ['id_tarea' => $tarea->id_tarea, 'id_alumno' => $alumno->id_alumno],
            $datos
        );

        return response()->json([
            'id_entrega' => $entrega->id_entrega,
            'archivo' => $entrega->archivo,
            'nombre_original' => $entrega->nombre_original,
            'link' => $entrega->link,
            'fecha_entrega' => $entrega->fecha_entrega,
            'estado' => $entrega->estado,
        ], 201);
    }

    // Presentar entrega (alumno) — la hace visible para el docente
    public function presentar(Request $request, $id_entrega)
    {
        $alumno = $request->user()->alumno;
        if (!$alumno) {
            return response()->json(['error' => 'Solo los alumnos pueden presentar tareas'], 403);
        }

        $entrega = EntregaTarea::where('id_entrega', $id_entrega)
            ->where('id_alumno', $alumno->id_alumno)
            ->first();

        if (!$entrega) {
            return response()->json(['error' => 'La entrega no existe o no te pertenece'], 404);
        }

        $tarea = Tarea::find($entrega->id_tarea);
        if ($tarea && $tarea->fecha_entrega && now()->greaterThan($tarea->fecha_entrega)) {
            return response()->json(['error' => 'La tarea ya venció'], 422);
        }

        $entrega->update([
            'estado' => 'entregada',
            'fecha_entrega' => now(),
        ]);

        $tarea->load('asignacion.curso', 'asignacion.catedratico');
        $catedraticoUsuario = $tarea->asignacion?->catedratico?->id_usuario;
        NotificacionService::paraUsuario(
            $catedraticoUsuario,
            "{$alumno->nombre} {$alumno->apellido} presentó la tarea \"{$tarea->titulo}\" del curso {$tarea->asignacion->curso->nombre_curso}."
        );

        return response()->json($entrega);
    }
}
