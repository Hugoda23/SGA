<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Catedratico extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'catedratico';
    protected $primaryKey = 'id_catedratico';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'codigo',
        'nombre',
        'apellido',
        'especialidad',
        'correo',
        'telefono',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'id_catedratico', 'id_catedratico');
    }
}
