<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    use HasFactory;

    protected $table = 'unidad';
    protected $primaryKey = 'id_unidad';
    public $timestamps = false;

    protected $fillable = [
        'id_asignacion',
        'numero_semana',
        'titulo',
        'temas',
        'competencia',
        'estado',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class, 'id_asignacion', 'id_asignacion');
    }

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'id_unidad', 'id_unidad');
    }
}
