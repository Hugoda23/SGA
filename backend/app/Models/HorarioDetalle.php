<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioDetalle extends Model
{
    use HasFactory;

    protected $table = 'horario_detalle';
    protected $primaryKey = 'id_horario';
    public $timestamps = false;

    protected $fillable = [
        'id_asignacion',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class, 'id_asignacion', 'id_asignacion');
    }
}
