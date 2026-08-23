<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anuncio extends Model
{
    protected $table = 'anuncio';
    protected $primaryKey = 'id_anuncio';
    public $timestamps = false;

    protected $fillable = [
        'id_asignacion',
        'titulo',
        'contenido',
        'fecha_publicacion',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class, 'id_asignacion', 'id_asignacion');
    }
}
