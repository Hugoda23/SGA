<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonaEvaluacion extends Model
{
    use HasFactory;

    protected $table = 'zona_evaluacion';
    protected $primaryKey = 'id_zona';
    public $timestamps = false;

    protected $fillable = [
        'id_asignacion',
        'nombre',
        'puntos',
        'posicion',
    ];

    protected $casts = [
        'puntos' => 'float',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class, 'id_asignacion', 'id_asignacion');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'id_zona', 'id_zona')->orderBy('id_evaluacion');
    }

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'id_zona', 'id_zona');
    }

    /**
     * Puntos que ya consumen las evaluaciones y tareas de esta zona.
     * Excluye el registro indicado de cada tipo (para poder editar uno sin
     * que se cuente a sí mismo como parte del consumo).
     */
    public function puntosConsumidos(?int $idEvaluacionExcluir = null, ?int $idTareaExcluir = null): float
    {
        $consumidoEvaluaciones = $this->evaluaciones()
            ->when($idEvaluacionExcluir, fn ($q) => $q->where('id_evaluacion', '!=', $idEvaluacionExcluir))
            ->sum('porcentaje');

        $consumidoTareas = $this->tareas()
            ->when($idTareaExcluir, fn ($q) => $q->where('id_tarea', '!=', $idTareaExcluir))
            ->sum('puntos');

        return (float) $consumidoEvaluaciones + (float) $consumidoTareas;
    }

    public function puntosDisponibles(?int $idEvaluacionExcluir = null, ?int $idTareaExcluir = null): float
    {
        return (float) $this->puntos - $this->puntosConsumidos($idEvaluacionExcluir, $idTareaExcluir);
    }
}
