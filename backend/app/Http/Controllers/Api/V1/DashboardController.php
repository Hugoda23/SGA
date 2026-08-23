<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Catedratico;
use App\Models\Curso;
use App\Models\Carrera;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $totalAlumnos = Alumno::count();
        $totalCatedraticos = Catedratico::count();
        $totalCursos = Curso::count();
        $totalInscripciones = Inscripcion::count();

        $alumnosPorCarrera = DB::table('alumno')
            ->join('carrera', 'alumno.id_carrera', '=', 'carrera.id_carrera')
            ->select('carrera.nombre_carrera as name', DB::raw('count(alumno.id_alumno) as value'))
            ->groupBy('carrera.nombre_carrera')
            ->get();

        return response()->json([
            'metrics' => [
                'alumnos' => $totalAlumnos,
                'catedraticos' => $totalCatedraticos,
                'cursos' => $totalCursos,
                'inscripciones' => $totalInscripciones,
            ],
            'charts' => [
                'alumnosPorCarrera' => $alumnosPorCarrera
            ]
        ]);
    }
}
