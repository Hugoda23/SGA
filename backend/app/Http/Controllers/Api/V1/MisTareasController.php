<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tarea;
use App\Models\EntregaTarea;
use Illuminate\Http\Request;

class MisTareasController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $alumno = $user->alumno;

        if (!$alumno) {
            return response()->json([], 200);
        }

        $inscripciones = $alumno->inscripciones()->pluck('id_asignacion');

        $tareas = Tarea::whereIn('id_asignacion', $inscripciones)
            ->with('asignacion.curso')
            ->orderBy('fecha_entrega', 'asc')
            ->get();

        $entregas = EntregaTarea::where('id_alumno', $alumno->id_alumno)
            ->whereIn('id_tarea', $tareas->pluck('id_tarea'))
            ->get()
            ->keyBy('id_tarea');

        return $tareas->map(function ($t) use ($entregas) {
            $entrega = $entregas->get($t->id_tarea);
            return [
                'id_tarea' => $t->id_tarea,
                'titulo' => $t->titulo,
                'descripcion' => $t->descripcion,
                'fecha_entrega' => $t->fecha_entrega,
                'permitir_link' => $t->permitir_link,
                'curso' => $t->asignacion?->curso?->nombre_curso,
                'mi_entrega' => $entrega ? [
                    'id_entrega' => $entrega->id_entrega,
                    'archivo' => $entrega->archivo,
                    'nombre_original' => $entrega->nombre_original,
                    'link' => $entrega->link,
                    'fecha_entrega' => $entrega->fecha_entrega,
                    'calificacion' => $entrega->calificacion,
                    'estado' => $entrega->estado,
                ] : null,
            ];
        });
    }
}
