<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinutes extends Model
{
    protected $fillable = [
        'meeting_id',
        'content',
        'status', // draft, published
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
} 