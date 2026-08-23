<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pensum extends Model
{
    use HasFactory;

    protected $table = 'pensum';
    protected $primaryKey = 'id_pensum';
    public $timestamps = false;

    protected $fillable = [
        'id_carrera',
        'id_curso',
        'id_grado',
        'obligatorio',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'id_grado', 'id_grado');
    }
}
