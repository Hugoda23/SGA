<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Configuracion;
use App\Models\ReporteGenerado;
use Illuminate\Http\Request;

class VerificacionController extends Controller
{
    public function verificar(Request $request, string $token)
    {
        $datos = $this->decodeToken($token);

        if (!$datos) {
            return view('verificacion', ['valido' => false, 'motivo' => 'Código de verificación inválido.']);
        }

        $reporte = ReporteGenerado::with('usuario')->find($datos['id']);

        if (!$reporte) {
            return view('verificacion', ['valido' => false, 'motivo' => 'El documento no existe en el sistema.']);
        }

        $alumno = isset($datos['alumno'])
            ? Alumno::with('carrera')->find($datos['alumno'])
            : null;

        $institucion = Configuracion::where('clave', 'nombre_institucion')->value('valor')
            ?? 'Institución Educativa';

        return view('verificacion', [
            'valido' => true,
            'institucion' => $institucion,
            'tipo_reporte' => $reporte->tipo_reporte,
            'fecha_generacion' => $reporte->fecha_generacion,
            'usuario' => $reporte->usuario,
            'alumno' => $alumno,
        ]);
    }

    private function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $sig] = $parts;

        $payload = strtr($payload, '-_', '+/');
        $payload = str_pad($payload, strlen($payload) + (4 - strlen($payload) % 4) % 4, '=');

        $expected = substr(hash_hmac('sha256', $payload, config('app.key')), 0, 32);

        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $decoded = json_decode(base64_decode($payload), true);

        return is_array($decoded) && isset($decoded['id']) ? $decoded : null;
    }
}
