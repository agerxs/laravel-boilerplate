<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingPaymentItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'meeting_payment_list_id',
        'attendee_id',
        'amount',
        'role',
        'payment_status', // pending, paid
        'payment_method',
        'payment_date',
        'comments'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    public function paymentList(): BelongsTo
    {
        return $this->belongsTo(MeetingPaymentList::class);
    }

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(MeetingAttendee::class);
    }
} 