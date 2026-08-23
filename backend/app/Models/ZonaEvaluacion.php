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
}
