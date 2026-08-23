<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Catedratico;
use App\Models\Rol;
use App\Models\Usuario;
use App\Traits\PreventsDeleteOnRelatedRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatedraticoController extends Controller
{
    use PreventsDeleteOnRelatedRecords;
    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 50), 1000));
        $q = trim((string) $request->query('q', ''));

        $query = Catedratico::with('usuario', 'asignaciones');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'ilike', "%{$q}%")
                    ->orWhere('apellido', 'ilike', "%{$q}%")
                    ->orWhere('especialidad', 'ilike', "%{$q}%")
                    ->orWhere('correo', 'ilike', "%{$q}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:usuario,username',
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'especialidad' => 'nullable|string|max:150',
            'correo' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
        ]);

        return DB::transaction(function () use ($validated) {
            $password = $this->generarPassword(
                $validated['nombre'],
                $validated['apellido'],
            );

            $usuario = Usuario::create([
                'username' => $validated['codigo'],
                'password' => bcrypt($password),
                'estado' => 'activo',
                'password_change_required' => true,
            ]);

            $rolCatedratico = Rol::where('nombre', 'catedratico')->firstOrFail();
            $usuario->roles()->attach($rolCatedratico->id_rol);

            $catedratico = Catedratico::create([
                'id_usuario' => $usuario->id_usuario,
                'codigo' => $validated['codigo'],
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'especialidad' => $validated['especialidad'] ?? null,
                'correo' => $validated['correo'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
            ]);

            $catedratico->load('usuario');

            return response()->json([
                'catedratico' => $catedratico,
                'password_temporal' => $password,
            ], 201);
        });
    }

    public function show(Catedratico $catedratico)
    {
        return $catedratico->load('usuario', 'asignaciones');
    }

    public function update(Request $request, Catedratico $catedratico)
    {
        $validated = $request->validate([
            'codigo' => 'nullable|string|max:50|unique:usuario,username,' . $catedratico->id_usuario . ',id_usuario',
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'especialidad' => 'nullable|string|max:150',
            'correo' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
        ]);

        if (isset($validated['codigo'])) {
            $catedratico->usuario->update(['username' => $validated['codigo']]);
        }

        $catedratico->update($validated);

        return response()->json($catedratico);
    }

    public function destroy(Catedratico $catedratico)
    {
        return $this->deleteWithGuard(
            $catedratico,
            fn ($c) => $c->asignaciones()->exists(),
            'No se puede eliminar el catedrático porque tiene asignaciones de cursos.'
        );
    }

    private function generarPassword(string $nombre, string $apellido): string
    {
        $inicialApellido = Str::lower(Str::substr($apellido, 0, 1));
        $primerNombre = str_replace(' ', '', Str::lower($nombre));

        return $inicialApellido . $primerNombre;
    }
}
