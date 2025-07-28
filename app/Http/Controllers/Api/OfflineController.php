<?php

namespace App\Http\Controllers\Api;

use App\Services\SyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class OfflineController extends Controller
{
    protected $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Enregistrer une opération hors ligne
     */
    public function queueOperation(Request $request)
    {
        $request->validate([
            'operation' => 'required|in:create,update,delete',
            'entity_type' => 'required|string',
            'entity_id' => 'required|integer',
            'data' => 'required|json'
        ]);

        DB::table('sync_queue')->insert([
            'operation' => $request->operation,
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
            'data' => $request->data,
            'created_at' => now(),
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Opération enregistrée avec succès']);
    }

    /**
     * Enregistrer une photo hors ligne
     */
    public function queuePhoto(Request $request)
    {
        $request->validate([
            'attendee_id' => 'required|integer',
            'photo' => 'required|image',
            'taken_at' => 'required|date'
        ]);

        // Sauvegarder la photo localement
        $localPath = $request->file('photo')->store('temp/photos');

        DB::table('offline_photos')->insert([
            'attendee_id' => $request->attendee_id,
            'local_path' => $localPath,
            'taken_at' => $request->taken_at,
            'status' => 'pending',
            'created_at' => now()
        ]);

        return response()->json(['message' => 'Photo enregistrée avec succès']);
    }

    /**
     * Synchroniser les données avec le serveur
     */
    public function sync()
    {
        $result = $this->syncService->sync();

        if ($result) {
            return response()->json(['message' => 'Synchronisation réussie']);
        }

        return response()->json(['message' => 'Erreur lors de la synchronisation'], 500);
    }

    /**
     * Obtenir l'état de la synchronisation
     */
    public function getSyncStatus()
    {
        $status = [
            'queue' => DB::table('sync_queue')->where('status', 'pending')->count(),
            'photos' => DB::table('offline_photos')->where('status', 'pending')->count(),
            'conflicts' => DB::table('sync_conflicts')->whereNull('resolution')->count(),
            'last_sync' => DB::table('sync_status')->max('last_sync_at')
        ];

        return response()->json($status);
    }

    /**
     * Résoudre un conflit
     */
    public function resolveConflict(Request $request)
    {
        $request->validate([
            'conflict_id' => 'required|integer',
            'resolution' => 'required|in:local,server,merged'
        ]);

        $conflict = DB::table('sync_conflicts')->find($request->conflict_id);

        if (!$conflict) {
            return response()->json(['message' => 'Conflit non trouvé'], 404);
        }

        if ($request->resolution === 'merged') {
            // Logique de fusion des données
            $localData = json_decode($conflict->local_data, true);
            $serverData = json_decode($conflict->server_data, true);
            
            // Fusionner les données (exemple)
            $mergedData = array_merge($serverData, $localData);
            
            // Mettre à jour l'entité avec les données fusionnées
            $this->syncService->updateEntityFromData(
                $conflict->entity_type,
                $conflict->entity_id,
                $mergedData
            );
        }

        DB::table('sync_conflicts')
            ->where('id', $request->conflict_id)
            ->update(['resolution' => $request->resolution]);

        return response()->json(['message' => 'Conflit résolu avec succès']);
    }

    /**
     * Obtenir les données locales
     */
    public function getLocalData(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|string',
            'last_sync' => 'nullable|date'
        ]);

        $query = DB::table($request->entity_type);

        if ($request->last_sync) {
            $query->where('updated_at', '>', $request->last_sync);
        }

        $data = $query->get();

        return response()->json($data);
    }
} 