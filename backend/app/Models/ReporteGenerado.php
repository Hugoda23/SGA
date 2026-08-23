<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteGenerado extends Model
{
    protected $table = 'reporte_generado';
    protected $primaryKey = 'id_reporte';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'tipo_reporte',
        'fecha_generacion',
        'tiempo_generacion',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
