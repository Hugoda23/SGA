<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Archivo;
use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Services\NotificacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    public function porAsignacion(Request $request, $id_asignacion)
    {
        $this->verificarCatedratico($request, $id_asignacion);

        return Material::where('id_asignacion', $id_asignacion)
            ->with('unidad', 'archivo')
            ->orderBy('fecha_publicacion', 'desc')
            ->get()
            ->map(fn ($m) => $this->serializar($m));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_asignacion' => 'required|exists:asignacion,id_asignacion',
            'id_unidad' => 'nullable|exists:unidad,id_unidad',
            'titulo' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:archivo,enlace',
            'archivo' => 'required_if:tipo,archivo|file|max:20480',
            'url' => 'required_if:tipo,enlace|url|max:500',
        ]);

        $this->verificarCatedratico($request, $validated['id_asignacion']);

        $idArchivo = null;
        if ($validated['tipo'] === 'archivo') {
            $file = $request->file('archivo');
            $ruta = $file->store('materiales', 'public');
            $archivo = Archivo::create([
                'nombre' => $file->getClientOriginalName(),
                'ruta' => $ruta,
                'tipo' => $file->getClientMimeType(),
                'fecha_subida' => now(),
            ]);
            $idArchivo = $archivo->id_archivo;
        }

        $material = Material::create([
            'id_asignacion' => $validated['id_asignacion'],
            'id_unidad' => $validated['id_unidad'] ?? null,
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'tipo' => $validated['tipo'],
            'id_archivo' => $idArchivo,
            'url' => $validated['url'] ?? null,
            'fecha_publicacion' => now(),
        ]);

        $asignacion = Asignacion::with('curso')->find($validated['id_asignacion']);
        $curso = $asignacion?->curso?->nombre_curso ?? 'el curso';
        NotificacionService::paraAlumnosDeAsignacion(
            $asignacion,
            "Nuevo material \"{$material->titulo}\" disponible en {$curso}."
        );

        return response()->json($this->serializar($material->load('unidad', 'archivo')), 201);
    }

    public function update(Request $request, $id_material)
    {
        $material = Material::findOrFail($id_material);
        $this->verificarCatedratico($request, $material->id_asignacion);

        $validated = $request->validate([
            'id_unidad' => 'nullable|exists:unidad,id_unidad',
            'titulo' => 'sometimes|string|max:200',
            'descripcion' => 'nullable|string',
            'url' => 'nullable|url|max:500',
        ]);

        if (array_key_exists('id_unidad', $validated)) {
            $material->id_unidad = $validated['id_unidad'];
        }
        if (array_key_exists('titulo', $validated)) {
            $material->titulo = $validated['titulo'];
        }
        if (array_key_exists('descripcion', $validated)) {
            $material->descripcion = $validated['descripcion'];
        }
        if (array_key_exists('url', $validated)) {
            $material->url = $validated['url'];
        }
        $material->save();

        return response()->json($this->serializar($material->load('unidad', 'archivo')));
    }

    public function destroy(Request $request, $id_material)
    {
        $material = Material::findOrFail($id_material);
        $this->verificarCatedratico($request, $material->id_asignacion);

        if ($material->id_archivo) {
            $archivo = Archivo::find($material->id_archivo);
            if ($archivo) {
                if (Storage::disk('public')->exists($archivo->ruta)) {
                    Storage::disk('public')->delete($archivo->ruta);
                }
                $archivo->delete();
            }
        }

        $material->delete();

        return response()->json(null, 204);
    }

    private function serializar(Material $m)
    {
        return [
            'id_material' => $m->id_material,
            'id_asignacion' => $m->id_asignacion,
            'id_unidad' => $m->id_unidad,
            'unidad' => $m->unidad ? [
                'id_unidad' => $m->unidad->id_unidad,
                'numero_semana' => $m->unidad->numero_semana,
                'titulo' => $m->unidad->titulo,
            ] : null,
            'titulo' => $m->titulo,
            'descripcion' => $m->descripcion,
            'tipo' => $m->tipo,
            'id_archivo' => $m->id_archivo,
            'archivo' => $m->archivo ? [
                'id_archivo' => $m->archivo->id_archivo,
                'nombre' => $m->archivo->nombre,
                'tipo' => $m->archivo->tipo,
            ] : null,
            'url' => $m->url,
            'fecha_publicacion' => $m->fecha_publicacion,
        ];
    }

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
