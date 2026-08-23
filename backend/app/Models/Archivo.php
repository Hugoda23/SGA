<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    protected $table = 'archivo';
    protected $primaryKey = 'id_archivo';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'ruta',
        'tipo',
        'fecha_subida',
    ];
}
