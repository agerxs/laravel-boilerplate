<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LocalCommittee extends Model
{
    protected $fillable = [
        'name',
        'description',
        'city',
        'address'
    ];

    public function members(): HasMany
    {
        return $this->hasMany(LocalCommitteeMember::class);
    }

    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class)
            ->withTimestamps();
    }
} 