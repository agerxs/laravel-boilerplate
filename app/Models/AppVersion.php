<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_code',
        'version_name',
        'apk_file',
        'release_notes',
    ];

    protected $casts = [
        'version_code' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Vérifier si cette version est plus récente qu'une autre
     */
    public function isNewerThan($versionCode): bool
    {
        return $this->version_code > (int) $versionCode;
    }

    /**
     * Obtenir l'URL de téléchargement
     */
    public function getDownloadUrlAttribute(): string
    {
        return url('/storage/' . $this->apk_file);
    }

    /**
     * Obtenir la taille du fichier APK
     */
    public function getFileSizeAttribute(): ?string
    {
        $path = storage_path('app/public/' . $this->apk_file);
        if (file_exists($path)) {
            $size = filesize($path);
            return $this->formatFileSize($size);
        }
        return null;
    }

    /**
     * Formater la taille du fichier
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Relations
     */
    public function downloads()
    {
        return $this->hasMany(\App\Models\AppDownload::class, 'app_version_id');
    }

    /**
     * Statistiques de téléchargement
     */
    public function getDownloadCountAttribute(): int
    {
        return $this->downloads()->where('download_status', 'completed')->count();
    }

    public function getFailedDownloadCountAttribute(): int
    {
        return $this->downloads()->where('download_status', 'failed')->count();
    }

    public function getActiveDeviceCountAttribute(): int
    {
        return $this->downloads()
            ->where('download_status', 'completed')
            ->distinct('device_id')
            ->count();
    }

    /**
     * Obtenir les appareils qui ont téléchargé cette version
     */
    public function getDownloadingDevices()
    {
        return $this->downloads()
            ->where('download_status', 'completed')
            ->with('device')
            ->get()
            ->pluck('device')
            ->unique('id');
    }
} 