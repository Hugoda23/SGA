<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    use HasFactory;

    protected $table = 'tarea';
    protected $primaryKey = 'id_tarea';
    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'descripcion',
        'puntos',
        'id_zona',
        'fecha_entrega',
        'permitir_link',
        'id_asignacion',
        'id_unidad',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'permitir_link' => 'boolean',
        'puntos' => 'float',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class, 'id_asignacion', 'id_asignacion');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'id_unidad', 'id_unidad');
    }

    public function zona()
    {
        return $this->belongsTo(ZonaEvaluacion::class, 'id_zona', 'id_zona');
    }

    public function entregas()
    {
        return $this->hasMany(EntregaTarea::class, 'id_tarea', 'id_tarea');
    }

    public function inscripciones()
    {
        return $this->hasManyThrough(
            Inscripcion::class,
            Asignacion::class,
            'id_asignacion',
            'id_asignacion',
            'id_asignacion',
            'id_asignacion'
        );
    }
}
