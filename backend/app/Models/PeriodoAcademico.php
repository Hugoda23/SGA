<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoAcademico extends Model
{
    use HasFactory;

    protected $table = 'periodo_academico';
    protected $primaryKey = 'id_periodo';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'id_periodo', 'id_periodo');
    }
}
