<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConfiguracionController extends Controller
{
    /**
     * Ajustes conocidos del sistema con su valor por defecto. Único lugar
     * que decide qué configuraciones existen — evita claves libres que se
     * puedan escribir mal y queden sin efecto en silencio.
     */
    private const DEFAULTS = [
        'nombre_institucion' => 'Instituto',
        'nota_minima' => '61',
        'version_sistema' => '1.0.0',
        'mantenimiento_activo' => '0',
        'mantenimiento_mensaje' => 'El sistema está en mantenimiento. Volvé a intentarlo más tarde.',
    ];

    public function index()
    {
        return response()->json($this->valoresActuales());
    }

    /**
     * GET /v1/sistema/estado — público, sin autenticación. Alimenta el
     * aviso de mantenimiento y el número de versión en la pantalla de
     * login, y el número de versión en el sidebar una vez adentro.
     */
    public function estadoPublico()
    {
        return response()->json([
            'version' => Configuracion::get('version_sistema', self::DEFAULTS['version_sistema']),
            'mantenimiento_activo' => Configuracion::get('mantenimiento_activo', '0') === '1',
            'mantenimiento_mensaje' => Configuracion::get('mantenimiento_mensaje', self::DEFAULTS['mantenimiento_mensaje']),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nombre_institucion' => 'required|string|max:150',
            'nota_minima' => 'required|integer|min:0|max:100',
            'version_sistema' => 'required|string|max:20',
            'mantenimiento_activo' => 'required|boolean',
            'mantenimiento_mensaje' => 'nullable|string|max:500',
        ]);

        $activoAnterior = Configuracion::get('mantenimiento_activo', '0') === '1';
        $activoNuevo = (bool) $validated['mantenimiento_activo'];

        $paraGuardar = $validated;
        $paraGuardar['mantenimiento_activo'] = $activoNuevo ? '1' : '0';
        $paraGuardar['mantenimiento_mensaje'] = $validated['mantenimiento_mensaje'] ?? '';

        foreach ($paraGuardar as $clave => $valor) {
            Configuracion::updateOrCreate(['clave' => $clave], ['valor' => (string) $valor]);
        }

        if ($activoAnterior !== $activoNuevo && Auth::id()) {
            Bitacora::create([
                'id_usuario' => Auth::id(),
                'accion' => 'ACTUALIZAR',
                'tabla_afectada' => 'configuracion',
                'id_registro' => null,
                'descripcion' => $activoNuevo
                    ? 'Se activó el modo mantenimiento del sistema.'
                    : 'Se desactivó el modo mantenimiento del sistema.',
                'fecha_hora' => now(),
            ]);
        }

        return response()->json($this->valoresActuales());
    }

    private function valoresActuales(): array
    {
        $valores = collect(self::DEFAULTS)
            ->map(fn ($default, $clave) => Configuracion::get($clave, $default))
            ->all();

        $valores['mantenimiento_activo'] = $valores['mantenimiento_activo'] === '1';

        return $valores;
    }
}
