<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentRequest extends Model
{
    protected $fillable = [
        'meeting_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
        'city',
        'postal_code',
        'notes',
        'status'
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
} 