<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalificacionFinal extends Model
{
    use HasFactory;

    protected $table = 'calificacion_final';
    protected $primaryKey = 'id_calificacion';
    public $timestamps = false;

    protected $fillable = [
        'id_inscripcion',
        'unidad_academica',
        'nota_final',
        'observaciones',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id_inscripcion');
    }
}
