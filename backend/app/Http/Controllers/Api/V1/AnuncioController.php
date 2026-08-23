<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use App\Models\Asignacion;
use App\Models\Catedratico;
use App\Models\Inscripcion;
use App\Services\NotificacionService;
use Illuminate\Http\Request;

class AnuncioController extends Controller
{
    public function porAsignacion(Request $request, $id_asignacion)
    {
        $this->verificarCatedratico($request, $id_asignacion);

        return Anuncio::where('id_asignacion', $id_asignacion)
            ->orderBy('fecha_publicacion', 'desc')
            ->get()
            ->map(fn ($a) => $this->serializar($a));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_asignacion' => 'required|exists:asignacion,id_asignacion',
            'titulo' => 'required|string|max:200',
            'contenido' => 'nullable|string',
        ]);

        $this->verificarCatedratico($request, $validated['id_asignacion']);

        $anuncio = Anuncio::create([
            'id_asignacion' => $validated['id_asignacion'],
            'titulo' => $validated['titulo'],
            'contenido' => $validated['contenido'] ?? null,
            'fecha_publicacion' => now(),
        ]);

        // Notificar a los alumnos inscritos
        $asignacion = Asignacion::with('curso')->find($validated['id_asignacion']);
        $inscripciones = Inscripcion::where('id_asignacion', $validated['id_asignacion'])
            ->with('alumno.usuario')
            ->get();
        $usuarios = $inscripciones->pluck('alumno.usuario')->filter();
        if ($usuarios->isNotEmpty()) {
            $curso = $asignacion?->curso?->nombre_curso ?? 'el curso';
            $mensaje = "Nuevo anuncio en {$curso}: {$anuncio->titulo}";
            NotificacionService::crearMultiple($usuarios->all(), $mensaje);
        }

        return response()->json($this->serializar($anuncio), 201);
    }

    public function update(Request $request, $id_anuncio)
    {
        $anuncio = Anuncio::findOrFail($id_anuncio);
        $this->verificarCatedratico($request, $anuncio->id_asignacion);

        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:200',
            'contenido' => 'nullable|string',
        ]);

        if (array_key_exists('titulo', $validated)) {
            $anuncio->titulo = $validated['titulo'];
        }
        if (array_key_exists('contenido', $validated)) {
            $anuncio->contenido = $validated['contenido'];
        }
        $anuncio->save();

        return response()->json($this->serializar($anuncio));
    }

    public function destroy(Request $request, $id_anuncio)
    {
        $anuncio = Anuncio::findOrFail($id_anuncio);
        $this->verificarCatedratico($request, $anuncio->id_asignacion);

        $anuncio->delete();

        return response()->json(null, 204);
    }

    private function serializar(Anuncio $a)
    {
        return [
            'id_anuncio' => $a->id_anuncio,
            'id_asignacion' => $a->id_asignacion,
            'titulo' => $a->titulo,
            'contenido' => $a->contenido,
            'fecha_publicacion' => $a->fecha_publicacion,
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
