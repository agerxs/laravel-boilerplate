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

    // Statuts d'export
    const EXPORT_STATUS_NOT_EXPORTED = 'not_exported';
    const EXPORT_STATUS_EXPORTED = 'exported';
    const EXPORT_STATUS_PAID = 'paid';

    protected $fillable = [
        'meeting_id',
        'submitted_by',
        'validated_by',
        'status',
        'total_amount',
        'submitted_at',
        'validated_at',
        'rejection_reason',
        'exported_at',
        'exported_by',
        'export_status',
        'export_reference',
        'paid_at',
        'paid_by'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
        'exported_at' => 'datetime',
        'paid_at' => 'datetime'
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

    public function exporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function paymentItems(): HasMany
    {
        return $this->hasMany(MeetingPaymentItem::class);
    }

    /**
     * Relation avec les pièces justificatives
     */
    public function justifications(): HasMany
    {
        return $this->hasMany(PaymentJustification::class);
    }

    /**
     * Vérifie si la liste a été exportée
     */
    public function isExported(): bool
    {
        return $this->export_status === self::EXPORT_STATUS_EXPORTED || 
               $this->export_status === self::EXPORT_STATUS_PAID;
    }

    /**
     * Vérifie si la liste a été payée
     */
    public function isPaid(): bool
    {
        return $this->export_status === self::EXPORT_STATUS_PAID;
    }

    /**
     * Marque la liste comme exportée
     */
    public function markAsExported(string $reference = null, int $userId = null): void
    {
        $this->update([
            'exported_at' => now(),
            'exported_by' => $userId,
            'export_status' => self::EXPORT_STATUS_EXPORTED,
            'export_reference' => $reference
        ]);
    }

    /**
     * Marque la liste comme payée
     */
    public function markAsPaid(int $userId = null): void
    {
        $this->update([
            'paid_at' => now(),
            'paid_by' => $userId,
            'export_status' => self::EXPORT_STATUS_PAID
        ]);
    }

    /**
     * Accesseur pour calculer le montant total dynamiquement
     */
    public function getTotalAmountAttribute()
    {
        return $this->paymentItems->sum('amount');
    }
} 