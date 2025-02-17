<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalCommitteeMember extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'first_name',
        'last_name',
        'phone'
    ];

    public function localCommittee(): BelongsTo
    {
        return $this->belongsTo(LocalCommittee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessor pour le nom complet
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
} 