<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingPaymentList extends Model
{
    use HasFactory, SoftDeletes;

    // Montants fixes
    const SUB_PREFET_AMOUNT = 50000;
    const SECRETARY_AMOUNT = 25000;
    const PARTICIPANT_AMOUNT = 15000;

    // Nombre de réunions requises pour le paiement
    const MEETINGS_REQUIRED_FOR_PAYMENT = 2;

    protected $fillable = [
        'meeting_id',
        'submitted_by',
        'validated_by',
        'status',
        'total_amount',
        'submitted_at',
        'validated_at',
        'rejection_reason'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime'
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