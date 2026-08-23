<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Seccion extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'seccion';
    protected $primaryKey = 'id_seccion';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'id_seccion', 'id_seccion');
    }
}
