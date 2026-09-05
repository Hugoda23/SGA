<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AlumnoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 50), 1000));
        $q = trim((string) $request->query('q', ''));

        $query = Alumno::with('usuario', 'carrera', 'grado', 'inscripciones');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'ilike', "%{$q}%")
                    ->orWhere('apellido', 'ilike', "%{$q}%")
                    ->orWhere('codigo_mineduc', 'ilike', "%{$q}%")
                    ->orWhere('numero_documento', 'ilike', "%{$q}%")
                    ->orWhere('correo', 'ilike', "%{$q}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_mineduc' => 'required|string|max:50|unique:usuario,username',
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'correo' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|string|in:masculino,femenino',
            'nacionalidad' => 'required|string|max:60',
            'tipo_documento' => 'required|string|in:cui,pasaporte',
            'numero_documento' => 'required|string|max:30|unique:alumno,numero_documento',
            'id_carrera' => 'nullable|exists:carrera,id_carrera',
            'estado_academico' => 'sometimes|string|in:activo,inactivo,egresado,retirado',
        ]);

        return DB::transaction(function () use ($validated) {
            $password = $this->generarPassword(
                $validated['nombre'],
                $validated['apellido'],
                $validated['fecha_nacimiento']
            );

            $usuario = Usuario::create([
                'username' => $validated['codigo_mineduc'],
                'password' => bcrypt($password),
                'estado' => 'activo',
                'password_change_required' => true,
            ]);

            $rolAlumno = Rol::where('nombre', 'alumno')->firstOrFail();
            $usuario->roles()->attach($rolAlumno->id_rol);

            $alumno = Alumno::create([
                'id_usuario' => $usuario->id_usuario,
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'codigo_mineduc' => $validated['codigo_mineduc'],
                'correo' => $validated['correo'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'genero' => $validated['genero'],
                'nacionalidad' => $validated['nacionalidad'],
                'tipo_documento' => $validated['tipo_documento'],
                'numero_documento' => $validated['numero_documento'],
                'id_carrera' => $validated['id_carrera'] ?? null,
                'estado_academico' => $validated['estado_academico'] ?? 'activo',
            ]);

            $alumno->load('usuario', 'carrera');

            return response()->json([
                'alumno' => $alumno,
                'password_temporal' => $password,
            ], 201);
        });
    }

    public function show(Alumno $alumno)
    {
        return $alumno->load('usuario', 'carrera', 'inscripciones');
    }

    public function update(Request $request, Alumno $alumno)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'codigo_mineduc' => 'nullable|string|max:50|unique:usuario,username,' . $alumno->id_usuario . ',id_usuario',
            'correo' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'sometimes|required|string|in:masculino,femenino',
            'nacionalidad' => 'sometimes|required|string|max:60',
            'tipo_documento' => 'sometimes|required|string|in:cui,pasaporte',
            'numero_documento' => 'sometimes|required|string|max:30|unique:alumno,numero_documento,' . $alumno->id_alumno . ',id_alumno',
            'id_carrera' => 'nullable|exists:carrera,id_carrera',
            'id_grado_actual' => 'nullable|exists:grado,id_grado',
            'id_seccion_actual' => 'nullable|exists:seccion,id_seccion',
            'estado_academico' => 'sometimes|string|in:activo,inactivo,egresado,retirado',
        ]);

        if (isset($validated['codigo_mineduc'])) {
            $alumno->usuario->update(['username' => $validated['codigo_mineduc']]);
        }

        $alumno->update($validated);

        return response()->json($alumno);
    }

    public function destroy(Alumno $alumno)
    {
        try {
            DB::transaction(function () use ($alumno) {
                $usuario = $alumno->usuario;
                $alumno->delete();
                if ($usuario) {
                    $usuario->delete();
                }
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23503') {
                return response()->json([
                    'message' => 'No se puede eliminar el alumno porque tiene registros asociados.',
                ], 409);
            }

            throw $e;
        }

        return response()->json(null, 204);
    }

    private function generarPassword(string $nombre, string $apellido, string $fechaNacimiento): string
    {
        $inicialApellido = Str::lower(Str::substr($apellido, 0, 1));
        $primerNombre = str_replace(' ', '', Str::lower($nombre));
        $anio = date('Y', strtotime($fechaNacimiento));

        return $inicialApellido . $primerNombre . $anio;
    }
}
