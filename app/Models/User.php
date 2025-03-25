<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Althinect\FilamentSpatieRolesPermissions\Concerns\HasSuperAdmin;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasSuperAdmin, SoftDeletes, HasApiTokens;

    /**
     * Les attributs qui peuvent être affectés en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locality_id',
        'organization_id',
        'job_function_id',
        'contract_type_id',
        'assignment_location_id',
        'cni_number',
        'phone',
        'whatsapp'
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
        'password' => 'hashed',
    ];

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function jobFunction(): BelongsTo
    {
        return $this->belongsTo(JobFunction::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function assignmentLocation(): BelongsTo
    {
        return $this->belongsTo(AssignmentLocation::class);
    }

    /**
     * Obtient le rôle principal de l'utilisateur
     */
    public function getPrimaryRole()
    {
        return $this->roles()->first();
    }
}
