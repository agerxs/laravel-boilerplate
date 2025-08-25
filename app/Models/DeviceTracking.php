<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceTracking extends Model
{
    use HasFactory;

    protected $table = 'device_tracking';
    
    protected $fillable = [
        'device_id',
        'device_name',
        'device_model',
        'platform',
        'platform_version',
        'app_version',
        'app_build_number',
        'device_fingerprint',
        'screen_resolution',
        'screen_density',
        'locale',
        'timezone',
        'is_tablet',
        'is_emulator',
        'additional_info',
        'last_seen_at',
    ];

    protected $casts = [
        'is_tablet' => 'boolean',
        'is_emulator' => 'boolean',
        'additional_info' => 'array',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(AppDownload::class, 'device_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(DeviceSession::class, 'device_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeTablets($query)
    {
        return $query->where('is_tablet', true);
    }

    public function scopeMobiles($query)
    {
        return $query->where('is_tablet', false);
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeActive($query)
    {
        return $query->where('last_seen_at', '>=', now()->subDays(30));
    }

    /**
     * Méthodes utilitaires
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function getDeviceTypeAttribute(): string
    {
        if ($this->is_tablet) {
            return 'Tablette';
        }
        return 'Mobile';
    }

    public function getPlatformLabelAttribute(): string
    {
        return match($this->platform) {
            'android' => 'Android',
            'ios' => 'iOS',
            'web' => 'Web',
            default => ucfirst($this->platform)
        };
    }

    public function getScreenInfoAttribute(): string
    {
        if ($this->screen_resolution && $this->screen_density) {
            return "{$this->screen_resolution} ({$this->screen_density})";
        }
        return $this->screen_resolution ?? 'N/A';
    }

    /**
     * Générer un identifiant unique d'appareil
     */
    public static function generateDeviceId(): string
    {
        return 'dev_' . uniqid() . '_' . time();
    }

    /**
     * Créer ou mettre à jour un appareil
     */
    public static function createOrUpdate(array $data): self
    {
        $device = static::firstOrCreate(
            ['device_id' => $data['device_id']],
            $data
        );

        if (!$device->wasRecentlyCreated) {
            $device->update(array_merge($data, ['last_seen_at' => now()]));
        }

        return $device;
    }
}
