<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Grado extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'grado';
    protected $primaryKey = 'id_grado';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'nivel',
    ];

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'id_grado', 'id_grado');
    }

    public function pensums()
    {
        return $this->hasMany(Pensum::class, 'id_grado', 'id_grado');
    }
}
