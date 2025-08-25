<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceTracking;
use App\Models\AppDownload;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeviceStatsController extends Controller
{
    /**
     * Afficher la page des statistiques des appareils
     */
    public function index()
    {
        // Statistiques des appareils (tablettes uniquement)
        $deviceStats = [
            'total_devices' => DeviceTracking::count(),
            'active_devices' => DeviceTracking::active()->count(),
            'recent_activity' => DeviceTracking::where('last_seen_at', '>=', now()->subDays(7))->count(),
        ];

        // Statistiques des téléchargements
        $downloadStats = [
            'total_downloads' => AppDownload::count(),
            'completed_downloads' => AppDownload::completed()->count(),
            'failed_downloads' => AppDownload::failed()->count(),
            'by_status' => AppDownload::selectRaw('download_status, COUNT(*) as count')
                ->groupBy('download_status')
                ->pluck('count', 'download_status'),
            'by_method' => AppDownload::selectRaw('download_method, COUNT(*) as count')
                ->groupBy('download_method')
                ->pluck('count', 'download_method'),
            'recent_downloads' => AppDownload::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        // Appareils récents avec leurs informations
        $recentDevices = DeviceTracking::with(['downloads', 'sessions'])
            ->orderBy('last_seen_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'device_name' => $device->device_name,
                    'device_model' => $device->device_model,
                    'platform' => $device->platform,
                    'platform_label' => $device->platform_label,
                    'is_tablet' => $device->is_tablet,
                    'device_type' => $device->device_type,
                    'app_version' => $device->app_version,
                    'last_seen_at' => $device->last_seen_at,
                    'screen_info' => $device->screen_info,
                    'download_count' => $device->downloads->where('download_status', 'completed')->count(),
                    'last_download' => $device->downloads->where('download_status', 'completed')->sortByDesc('created_at')->first()?->created_at,
                ];
            });

        // Statistiques par version d'APK
        $versionStats = AppVersion::with('downloads')
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->id,
                    'version_name' => $version->version_name,
                    'version_code' => $version->version_code,
                    'download_count' => $version->download_count,
                    'failed_count' => $version->failed_download_count,
                    'active_device_count' => $version->active_device_count,
                    'created_at' => $version->created_at,
                    'file_size' => $version->file_size,
                ];
            })
            ->sortByDesc('version_code');

        return Inertia::render('Admin/DeviceStats', [
            'deviceStats' => $deviceStats,
            'downloadStats' => $downloadStats,
            'recentDevices' => $recentDevices,
            'versionStats' => $versionStats,
        ]);
    }

    /**
     * API pour rafraîchir les statistiques (AJAX)
     */
    public function refreshStats()
    {
        // Même logique que index() mais retourne du JSON
        $deviceStats = [
            'total_devices' => DeviceTracking::count(),
            'active_devices' => DeviceTracking::active()->count(),
            'recent_activity' => DeviceTracking::where('last_seen_at', '>=', now()->subDays(7))->count(),
        ];

        $downloadStats = [
            'total_downloads' => AppDownload::count(),
            'completed_downloads' => AppDownload::completed()->count(),
            'failed_downloads' => AppDownload::failed()->count(),
            'by_status' => AppDownload::selectRaw('download_status, COUNT(*) as count')
                ->groupBy('download_status')
                ->pluck('count', 'download_status'),
            'by_method' => AppDownload::selectRaw('download_method, COUNT(*) as count')
                ->groupBy('download_method')
                ->pluck('count', 'download_method'),
            'recent_downloads' => AppDownload::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return response()->json([
            'success' => true,
            'deviceStats' => $deviceStats,
            'downloadStats' => $downloadStats,
        ]);
    }

    /**
     * Obtenir les détails d'un appareil spécifique
     */
    public function showDevice($deviceId)
    {
        $device = DeviceTracking::with(['downloads.appVersion', 'sessions'])
            ->where('device_id', $deviceId)
            ->firstOrFail();

        $deviceDetails = [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
            'device_model' => $device->device_model,
            'platform' => $device->platform,
            'platform_version' => $device->platform_version,
            'app_version' => $device->app_version,
            'app_build_number' => $device->app_build_number,
            'screen_resolution' => $device->screen_resolution,
            'screen_density' => $device->screen_density,
            'locale' => $device->locale,
            'timezone' => $device->timezone,
            'is_tablet' => $device->is_tablet,
            'is_emulator' => $device->is_emulator,
            'last_seen_at' => $device->last_seen_at,
            'created_at' => $device->created_at,
            'downloads' => $device->downloads->map(function ($download) {
                return [
                    'id' => $download->id,
                    'status' => $download->download_status,
                    'status_label' => $download->status_label,
                    'method' => $download->download_method,
                    'file_size' => $download->file_size_formatted,
                    'duration' => $download->duration_formatted,
                    'created_at' => $download->created_at,
                    'app_version' => $download->appVersion->version_name,
                ];
            }),
            'sessions' => $device->sessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'type' => $session->session_type,
                    'type_label' => $session->session_type_label,
                    'started_at' => $session->session_started_at,
                    'ended_at' => $session->session_ended_at,
                    'duration' => $session->duration_formatted,
                    'is_active' => $session->isActive(),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'device' => $deviceDetails,
        ]);
    }

    /**
     * Obtenir les statistiques détaillées des téléchargements
     */
    public function downloadDetails()
    {
        $downloadDetails = [
            'by_date' => AppDownload::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'by_hour' => AppDownload::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('hour')
                ->orderBy('hour')
                ->get(),
            'by_version' => AppVersion::with('downloads')
                ->get()
                ->map(function ($version) {
                    return [
                        'version_name' => $version->version_name,
                        'download_count' => $version->download_count,
                        'success_rate' => $version->downloads->count() > 0 
                            ? round(($version->downloads->where('download_status', 'completed')->count() / $version->downloads->count()) * 100, 2)
                            : 0,
                    ];
                }),
        ];

        return response()->json([
            'success' => true,
            'details' => $downloadDetails,
        ]);
    }
}
