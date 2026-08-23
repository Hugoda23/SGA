<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\LogsActivity;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, LogsActivity;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'username',
        'password',
        'estado',
        'password_change_required',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'permisos',
    ];

    protected $casts = [
        'password_change_required' => 'boolean',
    ];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuario_rol', 'id_usuario', 'id_rol');
    }

    public function alumno()
    {
        return $this->hasOne(Alumno::class, 'id_usuario', 'id_usuario');
    }

    public function catedratico()
    {
        return $this->hasOne(Catedratico::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Nombres de permisos únicos del usuario, unión de los permisos de sus roles.
     */
    public function getPermisosAttribute()
    {
        return $this->roles
            ->flatMap(fn ($rol) => $rol->permisos->pluck('nombre'))
            ->unique()
            ->values()
            ->all();
    }
}
