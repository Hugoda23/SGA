<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    protected $table = 'aula';
    protected $primaryKey = 'id_aula';
    public $timestamps = false;

    protected $fillable = [
        'nombre_aula',
        'capacidad',
        'id_edificio',
    ];

    public function edificio()
    {
        return $this->belongsTo(Edificio::class, 'id_edificio', 'id_edificio');
    }

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'id_aula', 'id_aula');
    }
}
