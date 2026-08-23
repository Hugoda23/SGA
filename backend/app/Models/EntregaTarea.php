<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntregaTarea extends Model
{
    protected $table = 'entrega_tarea';
    protected $primaryKey = 'id_entrega';
    public $timestamps = false;

    protected $fillable = [
        'id_tarea',
        'id_alumno',
        'archivo',
        'nombre_original',
        'link',
        'fecha_entrega',
        'calificacion',
        'estado',
    ];

    public function scopeEntregadas($query)
    {
        return $query->where('estado', 'entregada');
    }

    public function tarea()
    {
        return $this->belongsTo(Tarea::class, 'id_tarea', 'id_tarea');
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'id_alumno', 'id_alumno');
    }
}
