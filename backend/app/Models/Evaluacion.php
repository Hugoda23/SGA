<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'evaluacion';
    protected $primaryKey = 'id_evaluacion';
    public $timestamps = false;

    protected $fillable = [
        'id_asignacion',
        'id_zona',
        'unidad_academica',
        'nombre',
        'porcentaje',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class, 'id_asignacion', 'id_asignacion');
    }

    public function zona()
    {
        return $this->belongsTo(ZonaEvaluacion::class, 'id_zona', 'id_zona');
    }

    public function detalleCalificaciones()
    {
        return $this->hasMany(DetalleCalificacion::class, 'id_evaluacion', 'id_evaluacion');
    }
}
