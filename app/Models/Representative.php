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

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class, 'locality_id');
    }


    public function localCommittee(): BelongsTo
    {
        return $this->belongsTo(LocalCommittee::class);
    }
} 