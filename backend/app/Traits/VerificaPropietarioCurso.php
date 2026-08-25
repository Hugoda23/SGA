<?php

namespace App\Traits;

use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\Usuario;
use Illuminate\Http\Request;

/**
 * Verifica que el usuario autenticado pueda gestionar el contenido de una
 * asignación (zonas, unidades, materiales, anuncios, calificaciones,
 * asistencia): el catedrático dueño del curso, o personal administrativo
 * (admin/director/secretaria) para cualquier curso.
 *
 * Antes de este trait, cada controlador tenía su propia copia de este
 * método con un bug: si el usuario autenticado NO tenía perfil de
 * catedrático, la verificación no hacía nada y dejaba pasar la petición
 * — la intención era "así entran los admins", pero un ALUMNO tampoco
 * tiene perfil de catedrático, así que también entraba. Con eso, cualquier
 * alumno autenticado podía crear/editar/borrar la estructura de
 * evaluación de un curso ajeno.
 */
trait VerificaPropietarioCurso
{
    private function verificarCatedratico(Request $request, $id_asignacion)
    {
        $usuario = $request->user();
        $catedratico = Catedratico::where('id_usuario', $usuario->id_usuario)->first();

        if ($catedratico) {
            $asignacion = Asignacion::find($id_asignacion);
            if (!$asignacion || $asignacion->id_catedratico !== $catedratico->id_catedratico) {
                return response()->json(['error' => 'No autorizado para este curso'], 403)->throwResponse();
            }

            return;
        }

        if (!$this->esStaff($usuario)) {
            return response()->json(['error' => 'No autorizado para este curso'], 403)->throwResponse();
        }
    }

    /**
     * Para listados (index): el catedrático del usuario autenticado, o null
     * si es personal administrativo (admin/director/secretaria) — en ese
     * caso el listado no debe filtrarse por curso, ve todo.
     */
    private function catedraticoActual(Request $request): ?Catedratico
    {
        return Catedratico::where('id_usuario', $request->user()->id_usuario)->first();
    }

    /**
     * Personal administrativo con acceso a cualquier curso/alumno, sin
     * importar si tiene o no perfil de catedrático/alumno.
     */
    private function esStaff(Usuario $usuario): bool
    {
        return $usuario->roles()->whereIn('nombre', ['admin', 'director', 'secretaria'])->exists();
    }
}
