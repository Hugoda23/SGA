<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleCalificacion extends Model
{
    use HasFactory;

    protected $table = 'detalle_calificacion';
    protected $primaryKey = 'id_detalle';

    protected $fillable = [
        'id_evaluacion',
        'id_inscripcion',
        'nota',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class, 'id_evaluacion', 'id_evaluacion');
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id_inscripcion');
    }
}
