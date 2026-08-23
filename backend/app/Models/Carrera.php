<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    use HasFactory;

    protected $table = 'carrera';
    protected $primaryKey = 'id_carrera';
    public $timestamps = false;

    protected $fillable = [
        'nombre_carrera',
        'descripcion',
    ];

    public function alumnos()
    {
        return $this->hasMany(Alumno::class, 'id_carrera', 'id_carrera');
    }

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'curso_carrera', 'id_carrera', 'id_curso');
    }

    public function pensums()
    {
        return $this->hasMany(Pensum::class, 'id_carrera', 'id_carrera');
    }
}
