<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'role',
        'amount',
        'payment_status',
        'triggering_meetings',
        'is_paid',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'validated_at',
        'validated_by',
        'paid_at',
        'paid_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'payment_date' => 'date',
        'validated_at' => 'datetime',
        'paid_at' => 'datetime',
        'triggering_meetings' => 'array',
    ];

    /**
     * Relation avec la réunion
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec l'utilisateur qui a validé
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Relation avec l'utilisateur qui a payé
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Obtenir les réunions déclencheuses
     */
    public function getTriggeringMeetingsAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Définir les réunions déclencheuses
     */
    public function setTriggeringMeetingsAttribute($value)
    {
        $this->attributes['triggering_meetings'] = json_encode($value);
    }
} 