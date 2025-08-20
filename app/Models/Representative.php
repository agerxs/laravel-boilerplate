<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Representative extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'gender',
        'locality_id',
        'local_committee_id',
        'role',
        'createdAt',
        'updatedAt',
        'isSynced',
        'isDirty',
        'lastModified',
        'remoteId'
    ];

    protected $casts = [
        'isSynced' => 'boolean',
        'isDirty' => 'boolean',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
        'lastModified' => 'datetime',
    ];

    /**
     * Accesseur pour le genre en français
     */
    public function getGenderLabelAttribute()
    {
        return $this->gender === 'M' ? 'Masculin' : ($this->gender === 'F' ? 'Féminin' : 'Non spécifié');
    }

    /**
     * Accesseur pour le genre en format court
     */
    public function getGenderShortAttribute()
    {
        return $this->gender === 'M' ? 'M' : ($this->gender === 'F' ? 'F' : '-');
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class, 'locality_id');
    }


    public function localCommittee(): BelongsTo
    {
        return $this->belongsTo(LocalCommittee::class);
    }
} 