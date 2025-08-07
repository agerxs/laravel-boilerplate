<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinutes extends Model
{
    protected $fillable = [
        'meeting_id',
        'content',
        'status', // draft, published, pending_validation, validated
        'published_at',
        'validation_requested_at',
        'validated_at',
        'validated_by',
        'validation_comments',
        // Nouveaux champs pour les résultats des villages
        'people_to_enroll_count',
        'people_enrolled_count',
        'cmu_cards_available_count',
        'cmu_cards_distributed_count',
        'complaints_received_count',
        'complaints_processed_count',
        // Nouveaux champs pour les difficultés et recommandations
        'difficulties',
        'recommendations',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'validation_requested_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * L'utilisateur qui a validé le compte-rendu (président)
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Obtenir l'état lisible du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return [
            'draft' => 'Brouillon',
            'published' => 'Publié',
            'pending_validation' => 'En attente de validation',
            'validated' => 'Validé',
        ][$this->status] ?? 'Inconnu';
    }

    /**
     * Vérifier si le compte-rendu est en attente de validation
     */
    public function isPendingValidation(): bool
    {
        return $this->status === 'pending_validation';
    }

    /**
     * Vérifier si le compte-rendu est validé
     */
    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    /**
     * Calculer le taux d'enrôlement
     */
    public function getEnrollmentRateAttribute(): float
    {
        if ($this->people_to_enroll_count > 0) {
            return round(($this->people_enrolled_count / $this->people_to_enroll_count) * 100, 2);
        }
        return 0;
    }

    /**
     * Calculer le taux de distribution des cartes CMU
     */
    public function getCmuDistributionRateAttribute(): float
    {
        if ($this->cmu_cards_available_count > 0) {
            return round(($this->cmu_cards_distributed_count / $this->cmu_cards_available_count) * 100, 2);
        }
        return 0;
    }

    /**
     * Calculer le taux de traitement des réclamations
     */
    public function getComplaintProcessingRateAttribute(): float
    {
        if ($this->complaints_received_count > 0) {
            return round(($this->complaints_processed_count / $this->complaints_received_count) * 100, 2);
        }
        return 0;
    }
} 