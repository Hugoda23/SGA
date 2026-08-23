<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuracion';
    protected $primaryKey = 'id_config';
    public $timestamps = false;

    protected $fillable = [
        'clave',
        'valor',
    ];

    public static function get(string $clave, $default = null)
    {
        $row = static::where('clave', $clave)->first();

        return $row ? $row->valor : $default;
    }
}
