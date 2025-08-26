<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingPaymentItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'meeting_payment_list_id',
        'attendee_id',
        'amount',
        'role',
        'payment_status', // pending, paid
        'payment_method',
        'payment_date',
        'comments',
        'exported_at',
        'export_reference',
        'paid_at',
        'payment_reference'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'exported_at' => 'datetime',
        'paid_at' => 'datetime'
    ];

    public function paymentList(): BelongsTo
    {
        return $this->belongsTo(MeetingPaymentList::class, 'meeting_payment_list_id');
    }

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(MeetingAttendee::class, 'attendee_id');
    }

    /**
     * Vérifie si l'élément a été exporté
     */
    public function isExported(): bool
    {
        return !is_null($this->exported_at);
    }

    /**
     * Vérifie si l'élément a été payé
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Marque l'élément comme exporté
     */
    public function markAsExported(string $reference = null): void
    {
        $this->update([
            'exported_at' => now(),
            'export_reference' => $reference
        ]);
    }

    /**
     * Marque l'élément comme payé
     */
    public function markAsPaid(string $reference = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $reference
        ]);
    }
} 