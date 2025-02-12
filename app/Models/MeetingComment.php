<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingComment extends Model
{
    protected $fillable = [
        'meeting_id',
        'user_id',
        'content',
        'type',
        'commentable_id',
        'commentable_type'
    ];

    protected $with = ['user'];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }
} 