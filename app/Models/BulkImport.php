<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkImport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'local_committee_id',
        'import_type',
        'original_filename',
        'file_path',
        'file_type',
        'file_size',
        'import_data',
        'meetings_created',
        'attachments_count',
        'status',
        'error_message',
        'attachments_info',
    ];

    protected $casts = [
        'import_data' => 'array',
        'attachments_info' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur qui a fait l'import
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le comité local
     */
    public function localCommittee()
    {
        return $this->belongsTo(LocalCommittee::class);
    }

    /**
     * Relation avec les réunions créées lors de cet import
     */
    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'bulk_import_id');
    }

    /**
     * Vérifier si l'import est terminé avec succès
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Vérifier si l'import a échoué
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Vérifier si l'import est en cours
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Obtenir le statut formaté
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Terminé',
            'failed' => 'Échoué',
            default => 'Inconnu'
        };
    }

    /**
     * Obtenir la taille du fichier formatée
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Obtenir les informations sur les pièces jointes
     */
    public function getAttachmentsInfoAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Définir les informations sur les pièces jointes
     */
    public function setAttachmentsInfoAttribute($value)
    {
        $this->attributes['attachments_info'] = is_array($value) ? json_encode($value) : $value;
    }
}
