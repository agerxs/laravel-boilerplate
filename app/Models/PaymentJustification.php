<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentJustification extends Model
{
    use HasFactory, SoftDeletes;

    // Types de justifications
    const TYPE_RECEIPT = 'receipt';
    const TYPE_QUITTANCE = 'quittance';
    const TYPE_TRANSFER_PROOF = 'transfer_proof';
    const TYPE_BANK_STATEMENT = 'bank_statement';
    const TYPE_MOBILE_MONEY_PROOF = 'mobile_money_proof';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'meeting_payment_list_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'justification_type',
        'description',
        'reference_number',
        'amount',
        'payment_date'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'amount' => 'decimal:2',
        'payment_date' => 'date'
    ];

    /**
     * Relation avec la liste de paiement
     */
    public function paymentList(): BelongsTo
    {
        return $this->belongsTo(MeetingPaymentList::class, 'meeting_payment_list_id');
    }

    /**
     * Relation avec l'utilisateur qui a uploadé
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Obtenir le type de justification en français
     */
    public function getJustificationTypeTextAttribute(): string
    {
        return match($this->justification_type) {
            self::TYPE_RECEIPT => 'Reçu',
            self::TYPE_QUITTANCE => 'Quittance',
            self::TYPE_TRANSFER_PROOF => 'Preuve de virement',
            self::TYPE_BANK_STATEMENT => 'Relevé bancaire',
            self::TYPE_MOBILE_MONEY_PROOF => 'Preuve Mobile Money',
            self::TYPE_OTHER => 'Autre',
            default => 'Inconnu'
        };
    }

    /**
     * Obtenir la taille du fichier formatée
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Vérifier si le fichier est une image
     */
    public function isImage(): bool
    {
        return in_array(strtolower($this->file_type), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Vérifier si le fichier est un PDF
     */
    public function isPdf(): bool
    {
        return strtolower($this->file_type) === 'pdf';
    }

    /**
     * Obtenir l'URL publique du fichier
     */
    public function getPublicUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
