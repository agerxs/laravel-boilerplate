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
} 