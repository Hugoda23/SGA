<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Curso extends Model
{
    use LogsActivity;

    protected $table = 'curso';
    protected $primaryKey = 'id_curso';
    public $timestamps = false;

    protected $fillable = [
        'nombre_curso',
        'descripcion',
        'creditos',
    ];

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'curso_carrera', 'id_curso', 'id_carrera');
    }

    public function pensums()
    {
        return $this->hasMany(Pensum::class, 'id_curso', 'id_curso');
    }

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'id_curso', 'id_curso');
    }
}
