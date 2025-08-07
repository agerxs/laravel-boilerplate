<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VillageResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'localite_id',
        'submitted_by',
        'people_to_enroll_count',
        'people_enrolled_count',
        'cmu_cards_available_count',
        'cmu_cards_distributed_count',
        'complaints_received_count',
        'complaints_processed_count',
        'comments',
        'status',
        'submitted_at',
        'validated_at',
        'validated_by',
        'validation_comments',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    /**
     * Les propriétés à ajouter automatiquement lors de la sérialisation
     */
    protected $appends = [
        'enrollment_rate',
        'cmu_distribution_rate',
        'complaint_processing_rate',
        'status_label',
        'status_class',
    ];

    /**
     * Relation avec la réunion
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Relation avec le village (localité)
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Locality::class, 'localite_id');
    }

    /**
     * Relation avec l'utilisateur qui a soumis les résultats
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Relation avec l'utilisateur qui a validé les résultats
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
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

    /**
     * Vérifier si les résultats sont soumis
     */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Vérifier si les résultats sont validés
     */
    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    /**
     * Vérifier si les résultats sont en brouillon
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Obtenir l'état lisible du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return [
            'draft' => 'Brouillon',
            'submitted' => 'Soumis',
            'validated' => 'Validé',
        ][$this->status] ?? 'Inconnu';
    }

    /**
     * Obtenir la classe CSS pour le statut
     */
    public function getStatusClassAttribute(): string
    {
        return [
            'draft' => 'bg-gray-100 text-gray-700',
            'submitted' => 'bg-yellow-100 text-yellow-700',
            'validated' => 'bg-green-100 text-green-700',
        ][$this->status] ?? 'bg-gray-100 text-gray-700';
    }

    /**
     * Valider les données avant sauvegarde
     */
    public static function rules(): array
    {
        return [
            'people_to_enroll_count' => 'nullable|integer|min:0',
            'people_enrolled_count' => 'nullable|integer|min:0',
            'cmu_cards_available_count' => 'nullable|integer|min:0',
            'cmu_cards_distributed_count' => 'nullable|integer|min:0',
            'complaints_received_count' => 'nullable|integer|min:0',
            'complaints_processed_count' => 'nullable|integer|min:0',
            'comments' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Validation personnalisée
     */
    public function validateData(): array
    {
        $errors = [];

        if ($this->people_enrolled_count > $this->people_to_enroll_count && $this->people_to_enroll_count > 0) {
            $errors[] = 'Le nombre de personnes enrôlées ne peut pas dépasser le nombre de personnes à enrôler';
        }

        if ($this->cmu_cards_distributed_count > $this->cmu_cards_available_count && $this->cmu_cards_available_count > 0) {
            $errors[] = 'Le nombre de cartes distribuées ne peut pas dépasser le nombre de cartes disponibles';
        }

        if ($this->complaints_processed_count > $this->complaints_received_count && $this->complaints_received_count > 0) {
            $errors[] = 'Le nombre de réclamations traitées ne peut pas dépasser le nombre de réclamations reçues';
        }

        return $errors;
    }
}
