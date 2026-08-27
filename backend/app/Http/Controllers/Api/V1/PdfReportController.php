<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\Bitacora;
use App\Models\Configuracion;
use App\Models\EntregaTarea;
use App\Models\ReporteGenerado;
use App\Traits\VerificaPropietarioCurso;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class PdfReportController extends Controller
{
    use VerificaPropietarioCurso;

    /**
     * Un alumno solo puede ver/descargar su propio boletín, kárdex o
     * constancia; el personal administrativo (admin/director/secretaria)
     * puede ver el de cualquier alumno. Antes de este chequeo, cualquier
     * usuario autenticado (incluido otro alumno) podía descargar el
     * historial académico de cualquier persona solo cambiando el ID en
     * la URL.
     */
    private function verificarAccesoAlumno(Request $request, Alumno $alumno): void
    {
        $usuario = $request->user();

        if ($usuario->alumno && $usuario->alumno->id_alumno === $alumno->id_alumno) {
            return;
        }

        if (!$this->esStaff($usuario)) {
            abort(403, 'No autorizado para ver la información de este alumno.');
        }
    }

    private function logReporte(string $tipo): ?ReporteGenerado
    {
        $usuario = auth()->user();
        if ($usuario) {
            return ReporteGenerado::create([
                'id_usuario' => $usuario->id_usuario,
                'tipo_reporte' => $tipo,
                'fecha_generacion' => Carbon::now(),
                'tiempo_generacion' => null,
            ]);
        }

        return null;
    }

    private function makeToken(array $data): string
    {
        $payload = base64_encode(json_encode($data));
        $sig = substr(hash_hmac('sha256', $payload, config('app.key')), 0, 32);

        return rtrim(strtr($payload . '.' . $sig, '+/', '-_'), '=');
    }

    /**
     * Nombre de la institución configurado en el sistema (Configuración >
     * nombre_institucion), con el nombre de la app como respaldo. Se usa
     * en el encabezado de todos los reportes PDF para que muestren
     * siempre la misma identidad institucional.
     */
    private function institucionNombre(): string
    {
        return Configuracion::get('nombre_institucion', config('app.name'));
    }

    /**
     * Logo institucional codificado en base64, para incrustarlo directo en
     * el PDF (DomPDF renderiza imágenes locales de forma más confiable como
     * data URI que como ruta de archivo). Cacheado en memoria por request:
     * un mismo reporte nunca genera más de un PDF por llamada HTTP.
     */
    private static ?string $logoBase64Cache = null;

    private function logoBase64(): ?string
    {
        if (self::$logoBase64Cache !== null) {
            return self::$logoBase64Cache;
        }

        $path = public_path('images/logo.jpg');

        if (!is_file($path)) {
            return null;
        }

        return self::$logoBase64Cache = base64_encode(file_get_contents($path));
    }

    public function downloadBoletin(Request $request, $id)
    {
        $alumno = Alumno::with([
            'inscripciones.asignacion.curso',
            'inscripciones.asignacion.grado',
            'inscripciones.asignacion.seccion',
            'inscripciones.asignacion.periodo',
            'inscripciones.calificacionesFinales',
        ])->findOrFail($id);
        $this->verificarAccesoAlumno($request, $alumno);
        $reporte = $this->logReporte('boletin');
        $token = $reporte
            ? $this->makeToken(['id' => $reporte->id_reporte, 'alumno' => $alumno->id_alumno])
            : null;

        $qrCode = $token
            ? base64_encode(QrCode::format('svg')->size(100)->generate(url('/verificar/' . $token)))
            : null;

        $notaMinima = (int) Configuracion::get('nota_minima', 61);
        $notas = [];
        $suma = 0;
        $contadas = 0;

        foreach ($alumno->inscripciones as $inscripcion) {
            $asignacion = $inscripcion->asignacion;
            if (!$asignacion) {
                continue;
            }

            $calFinal = $inscripcion->calificacionesFinales->first();
            $nota = $calFinal?->nota_final !== null ? (float) $calFinal->nota_final : null;

            $gradoSeccion = trim(
                ($asignacion->grado?->nombre ?? '') . ' ' . ($asignacion->seccion?->nombre ?? '')
            );

            $notas[] = [
                'codigo' => 'CURSO-' . $asignacion->id_curso,
                'nombre' => $asignacion->curso?->nombre_curso ?? '—',
                'grado_seccion' => $gradoSeccion,
                'periodo' => $asignacion->periodo?->nombre ?? '—',
                'final' => $nota,
                'resultado' => $nota === null ? 'Sin nota' : ($nota >= $notaMinima ? 'Aprobado' : 'Reprobado'),
            ];

            if ($nota !== null) {
                $suma += $nota;
                $contadas++;
            }
        }

        $data = [
            'institucion' => $this->institucionNombre(),
            'logoBase64' => $this->logoBase64(),
            'tituloDoc' => 'Boletín de Calificaciones',
            'docNumero' => 'Boletín No. BOL-ALU-' . $alumno->id_alumno,
            'alumno' => $alumno,
            'fecha' => Carbon::now()->format('d/m/Y H:i'),
            'qrCode' => $qrCode,
            'notas' => $notas,
            'promedio' => $contadas > 0 ? round($suma / $contadas, 2) : null,
        ];

        $pdf = Pdf::loadView('pdf.boletin', $data);
        return $pdf->download('boletin_' . $alumno->id_alumno . '.pdf');
    }

    public function downloadKardex(Request $request, $id)
    {
        $alumno = Alumno::with([
            'inscripciones.asignacion.curso',
            'inscripciones.asignacion.periodo',
            'inscripciones.calificacionesFinales',
        ])->findOrFail($id);
        $this->verificarAccesoAlumno($request, $alumno);
        $this->logReporte('kardex');

        $notaMinima = (int) Configuracion::get('nota_minima', 61);
        $historial = [];
        $suma = 0;
        $contadas = 0;

        foreach ($alumno->inscripciones as $inscripcion) {
            $asignacion = $inscripcion->asignacion;
            if (!$asignacion) {
                continue;
            }

            $periodo = $asignacion->periodo?->nombre ?? 'Sin periodo';
            $calFinal = $inscripcion->calificacionesFinales->first();
            $nota = $calFinal?->nota_final !== null ? (float) $calFinal->nota_final : null;

            $historial[$periodo][] = [
                'nombre' => $asignacion->curso?->nombre_curso ?? '—',
                'nota' => $nota,
                'resultado' => $nota === null ? 'Sin nota' : ($nota >= $notaMinima ? 'Aprobado' : 'Reprobado'),
            ];

            if ($nota !== null) {
                $suma += $nota;
                $contadas++;
            }
        }

        $promedio_global = $contadas > 0 ? round($suma / $contadas, 2) : 0;
        $hash = hash('sha256', $alumno->id_alumno . Carbon::now()->toDateString() . $promedio_global);

        $data = [
            'institucion' => $this->institucionNombre(),
            'logoBase64' => $this->logoBase64(),
            'tituloDoc' => 'Certificado de Historial Académico (Kárdex)',
            'docNumero' => 'Kárdex No. KDX-ALU-' . $alumno->id_alumno,
            'alumno' => $alumno,
            'fecha' => Carbon::now()->format('d/m/Y'),
            'hash' => $hash,
            'historial' => $historial,
            'promedio_global' => $promedio_global,
        ];

        $pdf = Pdf::loadView('pdf.kardex', $data);
        return $pdf->download('kardex_' . $alumno->id_alumno . '.pdf');
    }

    public function downloadActa(Request $request, $id_asignacion)
    {
        $this->verificarCatedratico($request, $id_asignacion);

        $asignacion = Asignacion::with([
            'curso',
            'grado',
            'seccion',
            'periodo',
            'catedratico',
            'inscripciones.alumno',
            'inscripciones.calificacionesFinales',
            'inscripciones.detalleCalificaciones',
            'zonas.evaluaciones',
            'zonas.tareas',
            'evaluaciones',
        ])->findOrFail($id_asignacion);
        $this->logReporte('acta');

        $notaMinima = (int) Configuracion::get('nota_minima', 61);
        $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);

        // Zonas ordenadas (ya vienen por posicion/id_zona desde el modelo) —
        // son la estructura real de evaluación de este curso. Si el curso no
        // tiene zonas definidas, se documenta explícitamente en el PDF en
        // vez de inventar una columna "Zona"/"Examen" que no corresponde a
        // ninguna estructura real.
        $zonas = $asignacion->zonas;
        $usaZonas = $zonas->isNotEmpty();

        // Entregas de tareas vinculadas a alguna zona, agrupadas por alumno,
        // para sumarlas al desglose por zona sin hacer una consulta por
        // alumno (mismo patrón que CalificacionService::recalcularNotasFinales).
        $idsTareasEnZona = $zonas->flatMap->tareas->pluck('id_tarea');
        $idsAlumnos = $asignacion->inscripciones->pluck('id_alumno');
        $entregasPorAlumno = $idsTareasEnZona->isNotEmpty() && $idsAlumnos->isNotEmpty()
            ? EntregaTarea::whereIn('id_tarea', $idsTareasEnZona)
                ->whereIn('id_alumno', $idsAlumnos)->get()->groupBy('id_alumno')
            : collect();

        $alumnos = [];
        $aprobados = 0;
        $reprobados = 0;

        foreach ($asignacion->inscripciones as $index => $inscripcion) {
            $calFinal = $inscripcion->calificacionesFinales->first();
            $nota = $calFinal?->nota_final !== null ? (float) $calFinal->nota_final : null;

            $detalles = $inscripcion->detalleCalificaciones;
            $entregasAlumno = $entregasPorAlumno->get($inscripcion->id_alumno, collect())->keyBy('id_tarea');

            // Puntos obtenidos por zona (con el mismo tope por zona que usa
            // CalificacionService), para mostrar el desglose real de la nota
            // final — no un agregado aparte que pueda desalinearse de ella.
            $porZona = [];
            foreach ($zonas as $zonaObj) {
                $obtenido = 0.0;
                foreach ($zonaObj->evaluaciones as $ev) {
                    $detalle = $detalles->firstWhere('id_evaluacion', $ev->id_evaluacion);
                    $obtenido += (float) ($detalle?->nota ?? 0);
                }
                foreach ($zonaObj->tareas as $t) {
                    $obtenido += (float) ($entregasAlumno->get($t->id_tarea)?->calificacion ?? 0);
                }
                $porZona[$zonaObj->id_zona] = min($obtenido, (float) $zonaObj->puntos);
            }

            $resultado = $nota === null ? 'Sin nota' : ($nota >= $notaMinima ? 'Aprobado' : 'Reprobado');
            if ($resultado === 'Aprobado') {
                $aprobados++;
            } elseif ($resultado === 'Reprobado') {
                $reprobados++;
            }

            $alumnos[] = [
                'no' => $index + 1,
                'carnet' => $inscripcion->alumno?->codigo_mineduc ?? ('MAT-' . ($inscripcion->alumno?->id_alumno ?? '—')),
                'nombre' => $inscripcion->alumno ? trim($inscripcion->alumno->nombre . ' ' . $inscripcion->alumno->apellido) : '—',
                'por_zona' => $porZona,
                'total' => $nota,
                'letras' => $nota === null ? '—' : ucfirst($formatter->format(round($nota, 2))),
                'resultado' => $resultado,
            ];
        }

        $data = [
            'institucion' => $this->institucionNombre(),
            'logoBase64' => $this->logoBase64(),
            'tituloDoc' => 'Acta Oficial de Calificaciones',
            'docNumero' => 'Acta No. ACT-ASG-' . $asignacion->id_asignacion,
            'asignacion' => $asignacion,
            'fecha' => Carbon::now()->format('d/m/Y H:i'),
            'zonas' => $zonas,
            'usaZonas' => $usaZonas,
            'alumnos' => $alumnos,
            'stats' => [
                'asignados' => $asignacion->inscripciones->count(),
                'aprobados' => $aprobados,
                'reprobados' => $reprobados,
            ],
        ];

        $pdf = Pdf::loadView('pdf.acta', $data)->setPaper('a4', 'landscape');
        return $pdf->download('acta_asignacion_' . $id_asignacion . '.pdf');
    }

    public function downloadBitacora(Request $request)
    {
        $this->logReporte('bitacora');

        $startDate = Carbon::now()->subDays(30);
        $logs = Bitacora::where('fecha_hora', '>=', $startDate)->orderBy('fecha_hora', 'desc')->take(50)->get();

        $data = [
            'institucion' => $this->institucionNombre(),
            'logoBase64' => $this->logoBase64(),
            'tituloDoc' => 'Bitácora de Auditoría del Sistema',
            'logs' => $logs,
            'fecha' => Carbon::now()->format('d/m/Y H:i'),
            'usuario_generador' => auth()->user() ? auth()->user()->username : 'admin',
        ];

        $pdf = Pdf::loadView('pdf.bitacora', $data)->setPaper('a4', 'landscape');
        return $pdf->download('bitacora_auditoria.pdf');
    }

    /**
     * GET /v1/reportes/rendimiento
     * Rendimiento académico por periodo (y grado opcional): por asignación,
     * total inscritos, aprobados, reprobados y % de aprobación.
     */
    public function rendimientoPorPeriodo(Request $request)
    {
        $periodoId = (int) $request->query('periodo_id', 0);
        $gradoId = $request->query('grado_id') !== null ? (int) $request->query('grado_id') : null;

        $asignaciones = Asignacion::with([
            'curso', 'grado', 'seccion', 'periodo', 'catedratico',
            'inscripciones.calificacionesFinales',
        ])
            ->when($periodoId > 0, fn ($q) => $q->where('id_periodo', $periodoId))
            ->when($gradoId !== null, fn ($q) => $q->where('id_grado', $gradoId))
            ->get();

        $notaMinima = (int) Configuracion::get('nota_minima', 61);

        $filas = $asignaciones->map(function ($asig) use ($notaMinima) {
            $inscritos = $asig->inscripciones->filter(fn ($i) => $i->estado === 'activo');
            $notas = $inscritos
                ->map(fn ($i) => $i->calificacionesFinales->first()?->nota_final)
                ->filter(fn ($n) => $n !== null);
            $aprobados = $notas->filter(fn ($n) => (float) $n >= $notaMinima)->count();
            $conNota = $notas->count();

            return [
                'id_asignacion' => $asig->id_asignacion,
                'curso' => $asig->curso?->nombre_curso ?? '—',
                'grado' => $asig->grado?->nombre ?? '-',
                'seccion' => $asig->seccion?->nombre ?? '-',
                'catedratico' => $asig->catedratico ? "{$asig->catedratico->nombre} {$asig->catedratico->apellido}" : '—',
                'inscritos' => $inscritos->count(),
                'con_nota' => $conNota,
                'aprobados' => $aprobados,
                'reprobados' => $conNota - $aprobados,
                'sin_nota' => $inscritos->count() - $conNota,
                'promedio' => $notas->isNotEmpty() ? round((float) $notas->avg(), 2) : null,
                'porcentaje_aprobacion' => $conNota > 0 ? round($aprobados / $conNota * 100, 1) : null,
            ];
        })->values();

        $promedios = $filas->filter(fn ($f) => $f['promedio'] !== null);

        return response()->json([
            'asignaciones' => $filas,
            'resumen' => [
                'asignaciones' => $filas->count(),
                'inscritos' => $filas->sum('inscritos'),
                'aprobados' => $filas->sum('aprobados'),
                'reprobados' => $filas->sum('reprobados'),
                'promedio_general' => $promedios->isNotEmpty() ? round((float) $promedios->avg('promedio'), 2) : null,
            ],
        ]);
    }

    public function downloadConstancia(Request $request, $id)
    {
        $alumno = Alumno::findOrFail($id);
        $this->verificarAccesoAlumno($request, $alumno);
        $this->logReporte('constancia');

        $data = [
            'institucion' => $this->institucionNombre(),
            'logoBase64' => $this->logoBase64(),
            'tituloDoc' => 'Constancia de Inscripción',
            'docNumero' => 'Constancia No. CNS-ALU-' . $alumno->id_alumno,
            'alumno' => $alumno,
            'fecha' => Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
        ];

        $pdf = Pdf::loadView('pdf.constancia', $data);
        return $pdf->download('constancia_' . $alumno->id_alumno . '.pdf');
    }

    public function downloadAsistencia($id_asignacion, Request $request)
    {
        $this->verificarCatedratico($request, $id_asignacion);
        $this->logReporte('asistencia');

        $asignacion = Asignacion::with('curso', 'grado', 'seccion', 'inscripciones.alumno')->findOrFail($id_asignacion);
        $fecha = $request->query('fecha', Carbon::now()->toDateString());

        $asistencias = Asistencia::whereIn('id_inscripcion', $asignacion->inscripciones->pluck('id_inscripcion'))
            ->where('fecha', $fecha)
            ->get()
            ->keyBy('id_inscripcion');

        $alumnos = $asignacion->inscripciones->map(function ($ins) use ($asistencias) {
            $a = $asistencias->get($ins->id_inscripcion);
            return [
                'nombre' => $ins->alumno ? "{$ins->alumno->nombre} {$ins->alumno->apellido}" : '—',
                'estado' => $a?->estado ?? '',
            ];
        });

        $resumen = [
            'presentes' => $alumnos->where('estado', 'Presente')->count(),
            'ausentes' => $alumnos->where('estado', 'Ausente')->count(),
            'justificados' => $alumnos->where('estado', 'Justificado')->count(),
        ];

        $data = [
            'institucion' => $this->institucionNombre(),
            'logoBase64' => $this->logoBase64(),
            'tituloDoc' => 'Control de Asistencia',
            'curso' => $asignacion->curso?->nombre_curso ?? '—',
            'grado' => $asignacion->grado?->nombre ?? '—',
            'seccion' => $asignacion->seccion?->nombre ?? '—',
            'fecha' => Carbon::parse($fecha)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'alumnos' => $alumnos,
            'resumen' => $resumen,
            'usuario' => auth()->user()?->username ?? 'Sistema',
            'fecha_generacion' => Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm'),
        ];

        $pdf = Pdf::loadView('pdf.asistencia', $data);
        return $pdf->download("asistencia_{$id_asignacion}_{$fecha}.pdf");
    }

    public function downloadAsistenciaFinal(Request $request, $id_asignacion)
    {
        $this->verificarCatedratico($request, $id_asignacion);
        $this->logReporte('asistencia_final');

        $asignacion = Asignacion::with('curso', 'inscripciones.alumno')->findOrFail($id_asignacion);

        $asistencias = Asistencia::whereIn('id_inscripcion', $asignacion->inscripciones->pluck('id_inscripcion'))
            ->get()
            ->groupBy('id_inscripcion');

        $alumnos = $asignacion->inscripciones->map(function ($ins) use ($asistencias) {
            $registros = $asistencias->get($ins->id_inscripcion, collect());

            $presentes = $registros->where('estado', 'Presente')->count();
            $ausentes = $registros->where('estado', 'Ausente')->count();
            $justificados = $registros->where('estado', 'Justificado')->count();
            $sesiones = $presentes + $ausentes + $justificados;
            $pct = $sesiones > 0 ? round(($presentes * 100) / $sesiones) : null;

            if ($pct === null) {
                $estado = 'Sin registros';
                $clase = 'sin-registro';
            } elseif ($pct >= 80) {
                $estado = 'Aprueba';
                $clase = 'aprueba';
            } elseif ($pct >= 60) {
                $estado = 'En riesgo';
                $clase = 'en-riesgo';
            } else {
                $estado = 'Reprueba';
                $clase = 'reprueba';
            }

            return [
                'nombre' => $ins->alumno ? "{$ins->alumno->nombre} {$ins->alumno->apellido}" : '—',
                'sesiones' => $sesiones,
                'presentes' => $presentes,
                'ausentes' => $ausentes,
                'justificados' => $justificados,
                'pct' => $pct,
                'estado' => $estado,
                'clase' => $clase,
            ];
        });

        $totales = [
            'presentes' => $alumnos->sum('presentes'),
            'ausentes' => $alumnos->sum('ausentes'),
            'justificados' => $alumnos->sum('justificados'),
            'aprueba' => $alumnos->where('estado', 'Aprueba')->count(),
            'en_riesgo' => $alumnos->where('estado', 'En riesgo')->count(),
            'reprueba' => $alumnos->where('estado', 'Reprueba')->count(),
        ];

        $data = [
            'institucion' => $this->institucionNombre(),
            'logoBase64' => $this->logoBase64(),
            'tituloDoc' => 'Lista Final de Asistencia',
            'curso' => $asignacion->curso?->nombre_curso ?? '—',
            'grado' => $asignacion->grado?->nombre ?? '—',
            'seccion' => $asignacion->seccion?->nombre ?? '—',
            'alumnos' => $alumnos,
            'totales' => $totales,
            'usuario' => auth()->user()?->username ?? 'Sistema',
            'fecha_generacion' => Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm'),
        ];

        $pdf = Pdf::loadView('pdf.asistencia_final', $data);
        return $pdf->download("asistencia_final_{$id_asignacion}.pdf");
    }

    /**
     * GET /v1/reportes/pdf/listado-alumnos/{id_asignacion}
     * Listado de los alumnos activos de una asignación (curso + grado +
     * sección + periodo). Accesible al catedrático dueño del curso o a
     * personal administrativo (ver VerificaPropietarioCurso).
     */
    public function downloadListadoAlumnos(Request $request, $id_asignacion)
    {
        $this->verificarCatedratico($request, $id_asignacion);
        $this->logReporte('listado_alumnos');

        $asignacion = Asignacion::with([
            'curso',
            'grado',
            'seccion',
            'periodo',
            'catedratico',
            'inscripciones' => fn ($q) => $q->where('estado', 'activo'),
            'inscripciones.alumno.usuario',
        ])->findOrFail($id_asignacion);

        $alumnos = $asignacion->inscripciones
            ->sortBy(fn ($ins) => $ins->alumno?->apellido . ' ' . $ins->alumno?->nombre)
            ->values()
            ->map(fn ($ins) => [
                'nombre' => $ins->alumno ? "{$ins->alumno->nombre} {$ins->alumno->apellido}" : '—',
                'codigo' => $ins->alumno?->codigo_mineduc ?? '—',
                'clave' => $ins->alumno?->usuario?->username ?? '—',
            ]);

        $data = [
            'institucion' => $this->institucionNombre(),
            'logoBase64' => $this->logoBase64(),
            'tituloDoc' => 'Listado de Alumnos',
            'curso' => $asignacion->curso?->nombre_curso ?? '—',
            'grado' => $asignacion->grado?->nombre ?? '—',
            'seccion' => $asignacion->seccion?->nombre ?? '—',
            'periodo' => $asignacion->periodo?->nombre ?? '—',
            'catedratico' => $asignacion->catedratico ? "{$asignacion->catedratico->nombre} {$asignacion->catedratico->apellido}" : 'Pendiente de asignar',
            'alumnos' => $alumnos,
            'usuario' => auth()->user()?->username ?? 'Sistema',
            'fecha_generacion' => Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm'),
        ];

        $pdf = Pdf::loadView('pdf.listado_alumnos', $data);
        return $pdf->download("listado_alumnos_asignacion_{$id_asignacion}.pdf");
    }

    public function downloadAvanceProgramatico(Request $request, $id_asignacion)
    {
        $this->verificarCatedratico($request, $id_asignacion);
        $this->logReporte('avance_programatico');

        $asignacion = Asignacion::with('curso', 'grado', 'seccion', 'periodo', 'inscripciones', 'unidades.tareas')->findOrFail($id_asignacion);

        $unidades = $asignacion->unidades->map(function ($u) use ($asignacion) {
            return [
                'numero_semana' => $u->numero_semana,
                'titulo' => $u->titulo,
                'temas' => $u->temas,
                'competencia' => $u->competencia,
                'estado' => $u->estado,
                'tareas' => $u->tareas->map(function ($t) {
                    return $t->puntos !== null ? "{$t->titulo} ({$t->puntos} pts)" : $t->titulo;
                })->filter()->values(),
            ];
        });

        $data = [
            'institucion' => $this->institucionNombre(),
            'logoBase64' => $this->logoBase64(),
            'tituloDoc' => 'Avance Programático',
            'curso' => $asignacion->curso?->nombre_curso ?? '—',
            'grado' => $asignacion->grado?->nombre ?? '—',
            'seccion' => $asignacion->seccion?->nombre ?? '—',
            'periodo' => $asignacion->periodo?->nombre ?? '—',
            'total_alumnos' => $asignacion->inscripciones->count(),
            'unidades' => $unidades,
            'usuario' => auth()->user()?->username ?? 'Sistema',
            'fecha_generacion' => Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm'),
        ];

        $pdf = Pdf::loadView('pdf.avance_programatico', $data);
        return $pdf->download("avance_programatico_{$id_asignacion}.pdf");
    }
}
