<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    protected $table = 'asignacion';
    protected $primaryKey = 'id_asignacion';
    public $timestamps = false;

    protected $fillable = [
        'id_catedratico',
        'id_curso',
        'id_aula',
        'id_periodo',
        'id_grado',
        'id_seccion',
    ];

    public function catedratico()
    {
        return $this->belongsTo(Catedratico::class, 'id_catedratico', 'id_catedratico');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class, 'id_aula', 'id_aula');
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'id_periodo', 'id_periodo');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'id_grado', 'id_grado');
    }

    public function seccion()
    {
        return $this->belongsTo(Seccion::class, 'id_seccion', 'id_seccion');
    }

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'id_asignacion', 'id_asignacion');
    }

    public function unidades()
    {
        return $this->hasMany(Unidad::class, 'id_asignacion', 'id_asignacion')->orderBy('numero_semana');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_asignacion', 'id_asignacion');
    }

    public function horarios()
    {
        return $this->hasMany(HorarioDetalle::class, 'id_asignacion', 'id_asignacion');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'id_asignacion', 'id_asignacion');
    }

    public function zonas()
    {
        return $this->hasMany(ZonaEvaluacion::class, 'id_asignacion', 'id_asignacion')->orderBy('posicion')->orderBy('id_zona');
    }

    public function materiales()
    {
        return $this->hasMany(Material::class, 'id_asignacion', 'id_asignacion')->orderBy('fecha_publicacion', 'desc');
    }

    public function anuncios()
    {
        return $this->hasMany(Anuncio::class, 'id_asignacion', 'id_asignacion')->orderBy('fecha_publicacion', 'desc');
    }
}
