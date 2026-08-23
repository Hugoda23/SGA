<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Pensum;
use App\Services\HorarioService;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;

class AsignacionController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 50), 1000));
        $q = trim((string) $request->query('q', ''));

        $query = Asignacion::with('catedratico', 'curso', 'aula', 'periodo', 'grado', 'seccion', 'horarios', 'inscripciones');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->whereHas('curso', fn ($c) => $c->where('nombre_curso', 'ilike', "%{$q}%"))
                    ->orWhereHas('catedratico', fn ($c) => $c->where('nombre', 'ilike', "%{$q}%")->orWhere('apellido', 'ilike', "%{$q}%"))
                    ->orWhereHas('aula', fn ($c) => $c->where('nombre_aula', 'ilike', "%{$q}%"))
                    ->orWhereHas('periodo', fn ($c) => $c->where('nombre', 'ilike', "%{$q}%"));
            });
        }

        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_catedratico' => 'required|exists:catedratico,id_catedratico',
            'id_curso' => 'required|exists:curso,id_curso',
            'id_aula' => 'required|exists:aula,id_aula',
            'id_periodo' => 'required|exists:periodo_academico,id_periodo',
            'id_grado' => 'nullable|exists:grado,id_grado',
            'id_seccion' => 'nullable|exists:seccion,id_seccion',
        ]);

        $errorPensum = $this->validarCoherenciaPensum($validated['id_curso'], $validated['id_grado'] ?? null);
        if ($errorPensum) {
            return response()->json(['message' => $errorPensum], 422);
        }

        $asignacion = Asignacion::create($validated);

        return response()->json($asignacion, 201);
    }

    public function show(Asignacion $asignacion)
    {
        return $asignacion->load('catedratico', 'curso', 'aula', 'periodo', 'horarios', 'tareas', 'inscripciones', 'evaluaciones');
    }

    public function update(Request $request, Asignacion $asignacion, HorarioService $horarioService)
    {
        $validated = $request->validate([
            'id_catedratico' => 'sometimes|exists:catedratico,id_catedratico',
            'id_curso' => 'sometimes|exists:curso,id_curso',
            'id_aula' => 'sometimes|exists:aula,id_aula',
            'id_periodo' => 'sometimes|exists:periodo_academico,id_periodo',
            'id_grado' => 'nullable|exists:grado,id_grado',
            'id_seccion' => 'nullable|exists:seccion,id_seccion',
        ]);

        $nuevaAula = $validated['id_aula'] ?? $asignacion->id_aula;
        $nuevoPeriodo = $validated['id_periodo'] ?? $asignacion->id_periodo;
        $cambiaAulaOPeriodo = $nuevaAula != $asignacion->id_aula || $nuevoPeriodo != $asignacion->id_periodo;

        // Solo se revalida el pensum si la edición realmente toca curso o
        // grado — de lo contrario, una asignación ya existente cuya
        // combinación curso/grado no esté (todavía) en el pensum quedaría
        // bloqueada para editar cualquier otro campo (aula, catedrático, etc).
        if (array_key_exists('id_curso', $validated) || array_key_exists('id_grado', $validated)) {
            $nuevoCurso = $validated['id_curso'] ?? $asignacion->id_curso;
            $nuevoGrado = array_key_exists('id_grado', $validated) ? $validated['id_grado'] : $asignacion->id_grado;
            $errorPensum = $this->validarCoherenciaPensum($nuevoCurso, $nuevoGrado);
            if ($errorPensum) {
                return response()->json(['message' => $errorPensum], 422);
            }
        }

        if ($cambiaAulaOPeriodo) {
            // El aula/periodo cambian: los horarios ya cargados para esta
            // asignación deben revalidarse contra la nueva aula/periodo
            // (todavía no guardada) antes de aplicar el cambio.
            foreach ($asignacion->horarios as $horario) {
                $errores = $horarioService->verificarChoqueAula(
                    $asignacion->id_asignacion,
                    $nuevaAula,
                    $nuevoPeriodo,
                    $horario->dia_semana,
                    $horario->hora_inicio,
                    $horario->hora_fin,
                    $horario->id_horario
                );

                if (!empty($errores)) {
                    return response()->json([
                        'message' => 'No se pudo actualizar la asignación: el nuevo horario/aula choca con un horario existente.',
                        'errores' => $errores,
                    ], 422);
                }
            }
        }

        $asignacion->update($validated);

        return response()->json($asignacion);
    }

    public function destroy(Asignacion $asignacion)
    {
        return $this->deleteWithGuard(
            $asignacion,
            fn ($a) => $a->inscripciones()->exists() || $a->tareas()->exists() || $a->evaluaciones()->exists() || $a->horarios()->exists(),
            'No se puede eliminar la asignación porque tiene inscripciones, tareas, evaluaciones u horarios asociados.'
        );
    }

    /**
     * Un curso solo debe poder asignarse a un grado si el pensum lo
     * contempla para ese grado (en alguna carrera) — evita crear una
     * asignación (y por tanto inscripciones) para una combinación
     * curso/grado que no pertenece al plan de estudios.
     */
    private function validarCoherenciaPensum(?int $idCurso, ?int $idGrado): ?string
    {
        if ($idCurso === null || $idGrado === null) {
            return null;
        }

        $existeEnPensum = Pensum::where('id_curso', $idCurso)
            ->where('id_grado', $idGrado)
            ->exists();

        if (!$existeEnPensum) {
            return 'El curso seleccionado no forma parte del pensum del grado indicado.';
        }

        return null;
    }
}
