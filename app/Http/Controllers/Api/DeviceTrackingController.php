<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceTracking;
use App\Models\AppDownload;
use App\Models\DeviceSession;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DeviceTrackingController extends Controller
{
    /**
     * Enregistrer ou mettre à jour un appareil
     */
    public function registerDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'device_model' => 'nullable|string|max:255',
            'platform' => 'required|string|in:android,ios,web',
            'platform_version' => 'nullable|string|max:100',
            'app_version' => 'nullable|string|max:100',
            'app_build_number' => 'nullable|string|max:100',
            'device_fingerprint' => 'nullable|string|max:500',
            'screen_resolution' => 'nullable|string|max:100',
            'screen_density' => 'nullable|string|max:100',
            'locale' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:100',
            'is_tablet' => 'boolean',
            'is_emulator' => 'boolean',
            'additional_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['ip_address'] = $request->ip();
            $data['user_agent'] = $request->userAgent();

            $device = DeviceTracking::createOrUpdate($data);

            return response()->json([
                'success' => true,
                'message' => 'Appareil enregistré avec succès',
                'device' => $device
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de l\'appareil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Démarrer une session
     */
    public function startSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|exists:device_tracking,device_id',
            'user_id' => 'nullable|exists:users,id',
            'session_type' => 'nullable|string|in:mobile,tablet,web',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['ip_address'] = $request->ip();
            $data['user_agent'] = $request->userAgent();
            $data['session_type'] = $data['session_type'] ?? 'mobile';

            $session = DeviceSession::createSession($data);

            // Mettre à jour last_seen_at de l'appareil
            $device = DeviceTracking::where('device_id', $data['device_id'])->first();
            if ($device) {
                $device->updateLastSeen();
            }

            return response()->json([
                'success' => true,
                'message' => 'Session démarrée',
                'session' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage de la session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminer une session
     */
    public function endSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'required|string|exists:device_sessions,session_token',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token de session invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $session = DeviceSession::findActiveByToken($request->session_token);
            
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session non trouvée ou déjà terminée'
                ], 404);
            }

            $session->endSession();

            return response()->json([
                'success' => true,
                'message' => 'Session terminée',
                'session' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la terminaison de la session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer le début d'un téléchargement
     */
    public function startDownload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|exists:device_tracking,device_id',
            'app_version_id' => 'required|exists:app_versions,id',
            'user_id' => 'nullable|exists:users,id',
            'download_method' => 'nullable|string|in:app,web,direct',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['ip_address'] = $request->ip();
            $data['user_agent'] = $request->userAgent();
            $data['download_method'] = $data['download_method'] ?? 'app';

            $download = AppDownload::create($data);
            $download->markAsStarted();

            return response()->json([
                'success' => true,
                'message' => 'Téléchargement démarré',
                'download_id' => $download->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage du téléchargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer un téléchargement comme terminé
     */
    public function completeDownload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'download_id' => 'required|exists:app_downloads,id',
            'file_size' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $download = AppDownload::findOrFail($request->download_id);
            $download->markAsCompleted($request->file_size);

            return response()->json([
                'success' => true,
                'message' => 'Téléchargement terminé',
                'download' => $download
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la finalisation du téléchargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer un téléchargement comme échoué
     */
    public function failDownload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'download_id' => 'required|exists:app_downloads,id',
            'error_message' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $download = AppDownload::findOrFail($request->download_id);
            $download->markAsFailed($request->error_message);

            return response()->json([
                'success' => true,
                'message' => 'Téléchargement marqué comme échoué',
                'download' => $download
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage du téléchargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des appareils
     */
    public function getDeviceStats()
    {
        try {
            $stats = [
                'total_devices' => DeviceTracking::count(),
                'active_devices' => DeviceTracking::active()->count(),
                'tablets' => DeviceTracking::tablets()->count(),
                'mobiles' => DeviceTracking::mobiles()->count(),
                'by_platform' => DeviceTracking::selectRaw('platform, COUNT(*) as count')
                    ->groupBy('platform')
                    ->pluck('count', 'platform'),
                'recent_activity' => DeviceTracking::where('last_seen_at', '>=', now()->subDays(7))
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des téléchargements
     */
    public function getDownloadStats()
    {
        try {
            $stats = [
                'total_downloads' => AppDownload::count(),
                'completed_downloads' => AppDownload::completed()->count(),
                'failed_downloads' => AppDownload::failed()->count(),
                'by_status' => AppDownload::selectRaw('download_status, COUNT(*) as count')
                    ->groupBy('download_status')
                    ->pluck('count', 'download_status'),
                'by_method' => AppDownload::selectRaw('download_method, COUNT(*) as count')
                    ->groupBy('download_method')
                    ->pluck('count', 'download_method'),
                'recent_downloads' => AppDownload::where('created_at', '>=', now()->subDays(7))
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
