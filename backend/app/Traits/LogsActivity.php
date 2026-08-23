<?php

namespace App\Traits;

use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            self::logToBitacora('CREAR', $model);
        });

        static::updated(function ($model) {
            self::logToBitacora('ACTUALIZAR', $model);
        });

        static::deleted(function ($model) {
            self::logToBitacora('ELIMINAR', $model);
        });
    }

    protected static function logToBitacora($accion, $model)
    {
        $userId = Auth::id();

        if (!$userId) {
            return;
        }

        Bitacora::create([
            'id_usuario' => $userId,
            'accion' => $accion,
            'tabla_afectada' => $model->getTable(),
            'id_registro' => $model->getKey(),
            'descripcion' => "Se realizó la acción {$accion} en la tabla {$model->getTable()} con el ID {$model->getKey()}",
            'fecha_hora' => now(),
        ]);
    }
}
