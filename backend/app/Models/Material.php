<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'material';
    protected $primaryKey = 'id_material';
    public $timestamps = false;

    protected $fillable = [
        'id_asignacion',
        'id_unidad',
        'titulo',
        'descripcion',
        'tipo',
        'id_archivo',
        'url',
        'fecha_publicacion',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class, 'id_asignacion', 'id_asignacion');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'id_unidad', 'id_unidad');
    }

    public function archivo()
    {
        return $this->belongsTo(Archivo::class, 'id_archivo', 'id_archivo');
    }
}
