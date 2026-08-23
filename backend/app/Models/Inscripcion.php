<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inscripcion';
    protected $primaryKey = 'id_inscripcion';
    public $timestamps = false;

    protected $fillable = [
        'id_alumno',
        'id_asignacion',
        'fecha_inscripcion',
        'estado',
        'fecha_retiro',
    ];

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'id_alumno', 'id_alumno');
    }

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class, 'id_asignacion', 'id_asignacion');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_inscripcion', 'id_inscripcion');
    }

    public function calificacionesFinales()
    {
        return $this->hasMany(CalificacionFinal::class, 'id_inscripcion', 'id_inscripcion');
    }

    public function detalleCalificaciones()
    {
        return $this->hasMany(DetalleCalificacion::class, 'id_inscripcion', 'id_inscripcion');
    }
}
