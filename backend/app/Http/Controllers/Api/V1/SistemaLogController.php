<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SistemaLogController extends Controller
{
    /**
     * GET /v1/logs
     * Lee el archivo laravel.log, parsea cada entrada y la devuelve como JSON.
     * Filtros opcionales: nivel, buscar, limite.
     */
    public function index(Request $request)
    {
        $nivel = strtoupper((string) $request->query('nivel', ''));
        $buscar = (string) $request->query('buscar', '');
        $limite = (int) $request->query('limite', 500);
        $limite = max(1, min($limite, 2000));

        $path = storage_path('logs/laravel.log');

        if (!is_file($path)) {
            return response()->json(['total' => 0, 'logs' => []]);
        }

        $contenido = file_get_contents($path);
        $patron = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)$/m';

        if (!preg_match_all($patron, $contenido, $coincidencias, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return response()->json(['total' => 0, 'logs' => []]);
        }

        $fin = strlen($contenido);
        $logs = [];

        foreach ($coincidencias as $i => $match) {
            $inicio = $match[0][1];
            $siguiente = isset($coincidencias[$i + 1]) ? $coincidencias[$i + 1][0][1] : $fin;
            $bloque = substr($contenido, $inicio, $siguiente - $inicio);

            $mensaje = $match[4][0];
            $limpio = preg_replace('/\s+\{"exception":.*$/s', '', $mensaje);

            $logs[] = [
                'id' => $i + 1,
                'fecha' => $match[1][0],
                'canal' => $match[2][0],
                'nivel' => strtoupper($match[3][0]),
                'mensaje' => trim($limpio ?: $mensaje),
                'excepcion' => $this->extraerExcepcion($bloque),
                'archivo' => $this->extraerArchivo($bloque),
                'stacktrace' => $bloque,
            ];
        }

        $logs = array_reverse($logs);

        if ($nivel !== '') {
            $logs = array_values(array_filter($logs, fn ($l) => $l['nivel'] === $nivel));
        }

        if ($buscar !== '') {
            $b = strtolower($buscar);
            $logs = array_values(array_filter(
                $logs,
                fn ($l) => str_contains(
                    strtolower(implode(' ', [$l['mensaje'], $l['excepcion'], $l['archivo']])),
                    $b
                )
            ));
        }

        return response()->json([
            'total' => count($logs),
            'logs' => array_slice($logs, 0, $limite),
        ]);
    }

    /**
     * DELETE /v1/logs
     * Vacía el archivo de logs del sistema.
     */
    public function destroy()
    {
        $path = storage_path('logs/laravel.log');

        if (is_file($path)) {
            file_put_contents($path, '');
        }

        return response()->json(null, 204);
    }

    private function extraerExcepcion(string $bloque): ?string
    {
        if (preg_match('/\[object\] \(([A-Za-z0-9_\\\\]+)\(code: \d+\): /', $bloque, $m)) {
            return $m[1];
        }

        return null;
    }

    private function extraerArchivo(string $bloque): ?string
    {
        if (preg_match('/^#\d+ (\/[^\n]+?)\((\d+)\):/m', $bloque, $m)) {
            return $m[1].':'.$m[2];
        }

        return null;
    }
}
