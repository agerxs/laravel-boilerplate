<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'app_version_id',
        'user_id',
        'download_status',
        'download_method',
        'ip_address',
        'user_agent',
        'download_started_at',
        'download_completed_at',
        'file_size',
        'error_message',
        'download_metadata',
    ];

    protected $casts = [
        'download_started_at' => 'datetime',
        'download_completed_at' => 'datetime',
        'download_metadata' => 'array',
    ];

    /**
     * Relations
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(DeviceTracking::class, 'device_id');
    }

    public function appVersion(): BelongsTo
    {
        return $this->belongsTo(AppVersion::class, 'app_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('download_status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('download_status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('download_status', 'failed');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('download_method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Méthodes utilitaires
     */
    public function markAsStarted(): void
    {
        $this->update([
            'download_status' => 'started',
            'download_started_at' => now()
        ]);
    }

    public function markAsCompleted(int $fileSize = null): void
    {
        $this->update([
            'download_status' => 'completed',
            'download_completed_at' => now(),
            'file_size' => $fileSize
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'download_status' => 'failed',
            'error_message' => $errorMessage
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['download_status' => 'cancelled']);
    }

    public function getDownloadDurationAttribute(): ?int
    {
        if ($this->download_started_at && $this->download_completed_at) {
            return $this->download_started_at->diffInSeconds($this->download_completed_at);
        }
        return null;
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
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

    public function getStatusLabelAttribute(): string
    {
        return match($this->download_status) {
            'started' => 'En cours',
            'completed' => 'Terminé',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            default => ucfirst($this->download_status)
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->download_status) {
            'started' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }
}
