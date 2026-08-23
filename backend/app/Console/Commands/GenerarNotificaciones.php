<?php

namespace App\Console\Commands;

use App\Models\Asignacion;
use App\Models\HorarioDetalle;
use App\Models\Inscripcion;
use App\Models\Tarea;
use App\Services\NotificacionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerarNotificaciones extends Command
{
    protected $signature = 'sga:generar-notificaciones';
    protected $description = 'Genera notificaciones automáticas (clases próximas, tareas pendientes, etc.)';

    public function handle(): void
    {
        $this->notificarClasesManiana();
        $this->notificarTareasProximas();
        $this->info('Notificaciones generadas correctamente.');
    }

    private function notificarClasesManiana(): void
    {
        $diaSemana = Carbon::now()->addDay()->locale('es')->isoFormat('dddd');
        $maniana = Carbon::now()->addDay()->format('Y-m-d');
        $horarios = HorarioDetalle::where('dia_semana', 'like', $diaSemana)
            ->with('asignacion.inscripciones.alumno.usuario')
            ->get();

        foreach ($horarios as $horario) {
            $asignacion = $horario->asignacion;
            if (!$asignacion) continue;

            $cursoNombre = $asignacion->curso?->nombre_curso ?? 'Curso';
            $hora = $horario->hora_inicio ? Carbon::parse($horario->hora_inicio)->format('H:i') : '';

            foreach ($asignacion->inscripciones as $inscripcion) {
                $usuario = $inscripcion->alumno?->usuario;
                if ($usuario) {
                    NotificacionService::crear($usuario, "Recordatorio: clase de {$cursoNombre} mañana a las {$hora}.");
                }
            }

            $catedraticoUsuario = $asignacion->catedratico?->usuario;
            if ($catedraticoUsuario) {
                NotificacionService::crear($catedraticoUsuario, "Recordatorio: impartirá {$cursoNombre} mañana a las {$hora}.");
            }
        }
    }

    private function notificarTareasProximas(): void
    {
        $limite = Carbon::now()->addDays(2);
        $tareas = Tarea::whereDate('fecha_entrega', '<=', $limite)
            ->whereDate('fecha_entrega', '>=', Carbon::now())
            ->with('asignacion.inscripciones.alumno.usuario')
            ->get();

        foreach ($tareas as $tarea) {
            $asignacion = $tarea->asignacion;
            if (!$asignacion) continue;

            $fechaEntrega = Carbon::parse($tarea->fecha_entrega)->format('d/m/Y');
            foreach ($asignacion->inscripciones as $inscripcion) {
                $usuario = $inscripcion->alumno?->usuario;
                if ($usuario) {
                    NotificacionService::crear($usuario, "Tarea próxima: \"{$tarea->titulo}\" vence el {$fechaEntrega}.");
                }
            }
        }
    }
}
