<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\AttendancePhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SyncService
{
    /**
     * Synchroniser les données avec le serveur
     */
    public function sync()
    {
        try {
            DB::beginTransaction();

            // 1. Traiter la file d'attente des opérations
            $this->processSyncQueue();

            // 2. Synchroniser les photos
            $this->syncPhotos();

            // 3. Résoudre les conflits
            $this->resolveConflicts();

            // 4. Mettre à jour l'état de synchronisation
            $this->updateSyncStatus();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur de synchronisation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Traiter la file d'attente des opérations
     */
    private function processSyncQueue()
    {
        $queue = DB::table('sync_queue')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        foreach ($queue as $item) {
            try {
                switch ($item->operation) {
                    case 'create':
                        $this->createEntity($item);
                        break;
                    case 'update':
                        $this->updateEntity($item);
                        break;
                    case 'delete':
                        $this->deleteEntity($item);
                        break;
                }

                DB::table('sync_queue')
                    ->where('id', $item->id)
                    ->update([
                        'status' => 'processed',
                        'processed_at' => now()
                    ]);
            } catch (\Exception $e) {
                DB::table('sync_queue')
                    ->where('id', $item->id)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage()
                    ]);
            }
        }
    }

    /**
     * Synchroniser les photos
     */
    private function syncPhotos()
    {
        $photos = DB::table('offline_photos')
            ->where('status', 'pending')
            ->get();

        foreach ($photos as $photo) {
            try {
                // Upload de la photo
                $path = Storage::disk('public')->putFile(
                    'attendance-photos',
                    $photo->local_path
                );

                // Créer l'enregistrement dans la base de données
                AttendancePhoto::create([
                    'attendee_id' => $photo->attendee_id,
                    'photo_url' => $path,
                    'taken_at' => $photo->taken_at
                ]);

                // Supprimer la photo locale
                unlink($photo->local_path);

                DB::table('offline_photos')
                    ->where('id', $photo->id)
                    ->update(['status' => 'synced']);
            } catch (\Exception $e) {
                DB::table('offline_photos')
                    ->where('id', $photo->id)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage()
                    ]);
            }
        }
    }

    /**
     * Résoudre les conflits
     */
    private function resolveConflicts()
    {
        $conflicts = DB::table('sync_conflicts')
            ->whereNull('resolution')
            ->get();

        foreach ($conflicts as $conflict) {
            try {
                // Logique de résolution des conflits
                // Par exemple, utiliser la version la plus récente
                $localData = json_decode($conflict->local_data, true);
                $serverData = json_decode($conflict->server_data, true);

                $localTimestamp = Carbon::parse($localData['updated_at']);
                $serverTimestamp = Carbon::parse($serverData['updated_at']);

                if ($localTimestamp->gt($serverTimestamp)) {
                    $this->updateEntityFromData($conflict->entity_type, $conflict->entity_id, $localData);
                    $resolution = 'local';
                } else {
                    $this->updateEntityFromData($conflict->entity_type, $conflict->entity_id, $serverData);
                    $resolution = 'server';
                }

                DB::table('sync_conflicts')
                    ->where('id', $conflict->id)
                    ->update(['resolution' => $resolution]);
            } catch (\Exception $e) {
                \Log::error('Erreur de résolution de conflit: ' . $e->getMessage());
            }
        }
    }

    /**
     * Mettre à jour l'état de synchronisation
     */
    private function updateSyncStatus()
    {
        // Mettre à jour le statut de synchronisation pour toutes les entités
        $entities = [
            Meeting::class => 'meeting',
            MeetingAttendee::class => 'attendee',
            AttendancePhoto::class => 'photo'
        ];

        foreach ($entities as $model => $type) {
            $model::chunk(100, function ($items) use ($type) {
                foreach ($items as $item) {
                    DB::table('sync_status')->updateOrInsert(
                        [
                            'entity_type' => $type,
                            'entity_id' => $item->id
                        ],
                        [
                            'status' => 'synced',
                            'last_sync_at' => now()
                        ]
                    );
                }
            });
        }
    }

    /**
     * Créer une entité
     */
    private function createEntity($queueItem)
    {
        $data = json_decode($queueItem->data, true);
        
        switch ($queueItem->entity_type) {
            case 'meeting':
                Meeting::create($data);
                break;
            case 'attendee':
                MeetingAttendee::create($data);
                break;
            // Ajouter d'autres types d'entités si nécessaire
        }
    }

    /**
     * Mettre à jour une entité
     */
    private function updateEntity($queueItem)
    {
        $data = json_decode($queueItem->data, true);
        
        switch ($queueItem->entity_type) {
            case 'meeting':
                Meeting::where('id', $queueItem->entity_id)->update($data);
                break;
            case 'attendee':
                MeetingAttendee::where('id', $queueItem->entity_id)->update($data);
                break;
            // Ajouter d'autres types d'entités si nécessaire
        }
    }

    /**
     * Supprimer une entité
     */
    private function deleteEntity($queueItem)
    {
        switch ($queueItem->entity_type) {
            case 'meeting':
                Meeting::where('id', $queueItem->entity_id)->delete();
                break;
            case 'attendee':
                MeetingAttendee::where('id', $queueItem->entity_id)->delete();
                break;
            // Ajouter d'autres types d'entités si nécessaire
        }
    }

    /**
     * Mettre à jour une entité à partir des données
     */
    private function updateEntityFromData($entityType, $entityId, $data)
    {
        switch ($entityType) {
            case 'meeting':
                Meeting::where('id', $entityId)->update($data);
                break;
            case 'attendee':
                MeetingAttendee::where('id', $entityId)->update($data);
                break;
            // Ajouter d'autres types d'entités si nécessaire
        }
    }
} 