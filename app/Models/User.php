<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Althinect\FilamentSpatieRolesPermissions\Concerns\HasSuperAdmin;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasSuperAdmin;

    /**
     * Les attributs qui peuvent être affectés en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'position',
        'department',
        'address',
        'city',
        'postal_code',
    ];

    /**
     * Les attributs qui doivent être cachés pour les tableaux.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Les attributs qui devraient être castés.
     *
     * @var array<int, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
