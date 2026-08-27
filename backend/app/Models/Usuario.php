<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use App\Traits\LogsActivity;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, HasPushSubscriptions;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'username',
        'password',
        'estado',
        'password_change_required',
        'nombre',
        'apellido',
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

    /**
     * Excepciones de permisos propias del usuario (por encima de sus roles).
     * pivot.concedido = true otorga el permiso aunque el rol no lo tenga;
     * false se lo quita aunque el rol sí lo tenga.
     */
    public function permisosPropios()
    {
        return $this->belongsToMany(Permiso::class, 'usuario_permiso', 'id_usuario', 'id_permiso')
            ->withPivot('concedido')
            ->withTimestamps();
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
     * Nombres de permisos efectivos del usuario: la unión de los permisos de
     * sus roles, con las excepciones propias del usuario aplicadas encima
     * (concedidas de más o quitadas), ver [[permisosPropios]].
     */
    public function getPermisosAttribute()
    {
        $dePermisosRoles = $this->roles
            ->flatMap(fn ($rol) => $rol->permisos->pluck('nombre'));

        $denegados = $this->permisosPropios->filter(fn ($p) => !$p->pivot->concedido)->pluck('nombre');
        $concedidos = $this->permisosPropios->filter(fn ($p) => $p->pivot->concedido)->pluck('nombre');

        return $dePermisosRoles
            ->diff($denegados)
            ->merge($concedidos)
            ->unique()
            ->values()
            ->all();
    }
}
