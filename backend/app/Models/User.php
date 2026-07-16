<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'usuario',
        'email',
        'password',
        'id_rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'id_rol' => 'integer',
        ];
    }

    public function rol()
    {
        return $this->belongsTo(
            Role::class,
            'id_rol',
            'id_rol'
        );
    }

    public function empleado()
    {
        return $this->hasOne(
            Empleado::class,
            'id_user',
            'id'
        );
    }

    public function permisosPersonalizados()
    {
        return $this->hasMany(
            PermisoUser::class,
            'id_user',
            'id'
        );
    }

    public function pagosCreados()
    {
        return $this->hasMany(
            Pago::class,
            'id_user_crea',
            'id'
        );
    }

    public function cajasCreadas()
    {
        return $this->hasMany(
            Caja::class,
            'id_user_crea',
            'id'
        );
    }

    public function movimientosCarneCreados()
    {
        return $this->hasMany(
            MovimientoCarne::class,
            'id_user_crea',
            'id'
        );
    }

    public function permisosFinales()
    {
        $this->loadMissing(
            'rol.permisos',
            'permisosPersonalizados.permiso'
        );

        $permisosRol = $this->rol
            ? $this->rol->permisos->pluck('slug')->toArray()
            : [];

        $agregados = $this->permisosPersonalizados
            ->where('tipo', 'AGREGADO')
            ->pluck('permiso.slug')
            ->filter()
            ->toArray();

        $quitados = $this->permisosPersonalizados
            ->where('tipo', 'QUITADO')
            ->pluck('permiso.slug')
            ->filter()
            ->toArray();

        return collect($permisosRol)
            ->merge($agregados)
            ->unique()
            ->reject(
                fn ($permiso) => in_array(
                    $permiso,
                    $quitados,
                    true
                )
            )
            ->values();
    }

    public function tienePermiso(string $permiso): bool
    {
        return $this->permisosFinales()->contains($permiso);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
    public function comprasInternasAutorizadas()
{
    return $this->hasMany(
        CompraInterna::class,
        'id_user_autoriza',
        'id'
    );
}
public function reportesCreados()
{
    return $this->hasMany(
        Reporte::class,
        'id_user_crea',
        'id'
    );
}
}