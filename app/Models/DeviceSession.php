<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'user_id',
        'session_token',
        'session_type',
        'session_started_at',
        'session_ended_at',
        'ip_address',
        'user_agent',
        'session_data',
    ];

    protected $casts = [
        'session_started_at' => 'datetime',
        'session_ended_at' => 'datetime',
        'session_data' => 'array',
    ];

    /**
     * Relations
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(DeviceTracking::class, 'device_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereNull('session_ended_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('session_type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('session_started_at', [$startDate, $endDate]);
    }

    /**
     * Méthodes utilitaires
     */
    public function endSession(): void
    {
        $this->update(['session_ended_at' => now()]);
    }

    public function isActive(): bool
    {
        return is_null($this->session_ended_at);
    }

    public function getDurationAttribute(): int
    {
        $endTime = $this->session_ended_at ?? now();
        return $this->session_started_at->diffInSeconds($endTime);
    }

    public function getDurationFormattedAttribute(): string
    {
        $duration = $this->duration;
        
        if ($duration < 60) {
            return $duration . 's';
        } elseif ($duration < 3600) {
            return floor($duration / 60) . 'm ' . ($duration % 60) . 's';
        } else {
            $hours = floor($duration / 3600);
            $minutes = floor(($duration % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    public function getSessionTypeLabelAttribute(): string
    {
        return match($this->session_type) {
            'mobile' => 'Mobile',
            'tablet' => 'Tablette',
            'web' => 'Web',
            default => ucfirst($this->session_type)
        };
    }

    /**
     * Générer un token de session unique
     */
    public static function generateSessionToken(): string
    {
        return 'sess_' . uniqid() . '_' . time();
    }

    /**
     * Créer une nouvelle session
     */
    public static function createSession(array $data): self
    {
        return static::create(array_merge($data, [
            'session_token' => static::generateSessionToken(),
            'session_started_at' => now(),
        ]));
    }

    /**
     * Trouver une session active par token
     */
    public static function findActiveByToken(string $token): ?self
    {
        return static::where('session_token', $token)
            ->whereNull('session_ended_at')
            ->first();
    }

    /**
     * Nettoyer les sessions expirées
     */
    public static function cleanupExpiredSessions(int $maxAgeHours = 24): int
    {
        $cutoffTime = now()->subHours($maxAgeHours);
        
        return static::where('session_started_at', '<', $cutoffTime)
            ->whereNull('session_ended_at')
            ->update(['session_ended_at' => now()]);
    }
}
