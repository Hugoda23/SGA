<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Alumno extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'alumno';
    protected $primaryKey = 'id_alumno';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'nombre',
        'apellido',
        'codigo_mineduc',
        'correo',
        'telefono',
        'fecha_nacimiento',
        'id_carrera',
        'id_grado_actual',
        'estado_academico',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_alumno', 'id_alumno');
    }

    public function entregasTarea()
    {
        return $this->hasMany(EntregaTarea::class, 'id_alumno', 'id_alumno');
    }
}
