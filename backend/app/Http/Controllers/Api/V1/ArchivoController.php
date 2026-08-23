<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArchivoController extends Controller
{
    public function index()
    {
        return Archivo::all();
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'archivo' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,zip,rar,doc,docx,ppt,pptx,xls,xlsx,odt,txt,jpg,jpeg,png,gif,webp',
                'mimetypes:application/pdf,application/zip,application/x-rar-compressed,application/vnd.rar,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.oasis.opendocument.text,text/plain,image/jpeg,image/png,image/gif,image/webp',
            ],
            'nombre' => 'nullable|string|max:255',
            'tipo' => 'nullable|string|max:50',
        ]);

        $file = $request->file('archivo');
        $nombreOriginal = $validated['nombre'] ?? $file->getClientOriginalName();
        $ruta = $file->storeAs(
            'archivos',
            bin2hex(random_bytes(16)).'.'.$file->getClientOriginalExtension(),
            'public'
        );

        $archivo = Archivo::create([
            'nombre' => $nombreOriginal,
            'ruta' => $ruta,
            'tipo' => $validated['tipo'] ?? $file->getClientMimeType(),
            'fecha_subida' => now(),
        ]);

        return response()->json($archivo, 201);
    }

    public function descargar(Archivo $archivo)
    {
        if (!Storage::disk('public')->exists($archivo->ruta)) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }

        return Storage::disk('public')->download($archivo->ruta, $archivo->nombre);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'ruta' => 'required|string|max:255',
            'tipo' => 'nullable|string|max:50',
        ]);

        $archivo = Archivo::create($validated);
        return response()->json($archivo, 201);
    }

    public function show(Archivo $archivo)
    {
        return $archivo;
    }

    public function update(Request $request, Archivo $archivo)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'ruta' => 'sometimes|string|max:255',
            'tipo' => 'nullable|string|max:50',
        ]);

        $archivo->update($validated);
        return response()->json($archivo);
    }

    public function destroy(Archivo $archivo)
    {
        if ($archivo->ruta && Storage::disk('public')->exists($archivo->ruta)) {
            Storage::disk('public')->delete($archivo->ruta);
        }
        $archivo->delete();
        return response()->json(null, 204);
    }
}
