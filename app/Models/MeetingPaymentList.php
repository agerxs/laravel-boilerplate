<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingPaymentList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'meeting_id',
        'submitted_by',
        'validated_by',
        'submitted_at',
        'validated_at',
        'status', // draft, submitted, validated, rejected
        'rejection_reason',
        'total_amount',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function paymentItems(): HasMany
    {
        return $this->hasMany(MeetingPaymentItem::class);
    }
} 