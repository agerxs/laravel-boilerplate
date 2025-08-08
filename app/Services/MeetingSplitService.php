<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Locality;
use App\Models\MeetingAttendee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeetingSplitService
{
    /**
     * Éclater une réunion principale en plusieurs sous-réunions par groupes de villages
     */
    public function splitMeeting(Meeting $parentMeeting, array $subMeetingsData): array
    {
        if (!$parentMeeting->canBeSplit()) {
            throw new \Exception('Cette réunion ne peut pas être éclatée');
        }

        // Vérifier qu'il reste des villages disponibles
        $availableVillages = $this->getAvailableVillages($parentMeeting);
        if (empty($availableVillages)) {
            throw new \Exception('Aucun village disponible pour créer de nouvelles sous-réunions');
        }

        $createdSubMeetings = [];

        DB::beginTransaction();
        try {
            foreach ($subMeetingsData as $subMeetingData) {
                $subMeeting = $this->createSubMeeting($parentMeeting, $subMeetingData);
                $villageIds = array_column($subMeetingData['villages'], 'id');
                $this->assignAttendeesToSubMeeting($subMeeting, $villageIds);
                $createdSubMeetings[] = $subMeeting;
            }

            DB::commit();
            Log::info("Réunion {$parentMeeting->id} éclatée en " . count($createdSubMeetings) . " sous-réunions");
            
            return $createdSubMeetings;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de l'éclatement de la réunion {$parentMeeting->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Créer une sous-réunion
     */
    private function createSubMeeting(Meeting $parentMeeting, array $subMeetingData): Meeting
    {
        // Créer un titre basé sur les villages
        $villageNames = array_column($subMeetingData['villages'], 'name');
        $title = $parentMeeting->title . ' - ' . implode(', ', $villageNames);
        
        $subMeeting = new Meeting([
            'title' => $title,
            'local_committee_id' => $parentMeeting->local_committee_id,
            'scheduled_date' => $parentMeeting->scheduled_date,
            'location' => $subMeetingData['location'],
            'target_enrollments' => 0, // Sera calculé lors de l'assignation des participants
            'actual_enrollments' => 0,
            'status' => 'planned',
            'created_by' => $parentMeeting->created_by,
            'parent_meeting_id' => $parentMeeting->id,
        ]);

        $subMeeting->save();
        return $subMeeting;
    }

    /**
     * Assigner les participants à une sous-réunion selon les villages
     */
    private function assignAttendeesToSubMeeting(Meeting $subMeeting, array $villageIds): void
    {
        Log::info("Assignation des participants pour la sous-réunion {$subMeeting->id} - Villages: " . implode(', ', $villageIds));
        
        // Récupérer tous les participants de la réunion parent qui appartiennent aux villages spécifiés
        $attendees = MeetingAttendee::where('meeting_id', $subMeeting->parent_meeting_id)
            ->whereIn('localite_id', $villageIds)
            ->get();

        $targetEnrollments = 0;

        // Créer des participants pour chaque village assigné
        foreach ($villageIds as $villageId) {
            $village = Locality::find($villageId);
            Log::info("Traitement du village: {$village->name} (ID: {$villageId})");
            
            // Vérifier si il y a déjà des participants pour ce village dans la réunion parent
            $existingAttendees = $attendees->where('localite_id', $villageId);
            
            if ($existingAttendees->count() > 0) {
                Log::info("Copie de {$existingAttendees->count()} participants existants pour le village {$village->name}");
                // Copier les participants existants
                foreach ($existingAttendees as $attendee) {
                    $newAttendee = new MeetingAttendee([
                        'meeting_id' => $subMeeting->id,
                        'localite_id' => $attendee->localite_id,
                        'representative_id' => $attendee->representative_id,
                        'attendance_status' => 'expected',
                        'is_expected' => true,
                        'is_present' => falsemain,
                        'payment_status' => 'pending',
                    ]);
                    $newAttendee->save();
                    $targetEnrollments++;
                }
            } else {
                // Vérifier s'il y a des représentants pour ce village
                $representatives = \App\Models\Representative::where('locality_id', $villageId)
                    ->get();
                
                Log::info("Village {$village->name}: {$representatives->count()} représentants actifs trouvés");
                
                if ($representatives->count() > 0) {
                    // Créer des participants pour chaque représentant du village
                    foreach ($representatives as $representative) {
                        Log::info("Création d'un participant pour le représentant: {$representative->name} ({$representative->role})");
                        $newAttendee = new MeetingAttendee([
                            'meeting_id' => $subMeeting->id,
                            'localite_id' => $villageId,
                            'representative_id' => $representative->id,
                            'name' => $representative->name,
                            'phone' => $representative->phone,
                            'role' => $representative->role,
                            'attendance_status' => 'expected',
                            'is_expected' => true,
                            'is_present' => false,
                            'payment_status' => 'pending',
                        ]);
                        $newAttendee->save();
                        $targetEnrollments++;
                    }
                } else {
                    Log::info("Aucun représentant actif trouvé pour le village {$village->name} - Aucun participant créé");
                }
                // Si pas de représentants pour ce village, ne créer aucun participant
            }
        }

        Log::info("Sous-réunion {$subMeeting->id}: {$targetEnrollments} participants créés au total");
        
        // Mettre à jour le nombre de participants attendus
        $subMeeting->update(['target_enrollments' => $targetEnrollments]);
    }

    /**
     * Obtenir les villages disponibles pour une réunion
     */
    public function getAvailableVillages(Meeting $meeting): array
    {
        // Récupérer la localité du comité local
        $committeeLocality = $meeting->localCommittee->locality;
        
        if (!$committeeLocality) {
            return [];
        }

        // Récupérer directement les villages de cette sous-préfecture
        $villages = Locality::where('parent_id', $committeeLocality->id)
            ->whereHas('type', function($query) {
                $query->where('name', 'village');
            })
            ->get();

        // Récupérer les villages déjà assignés dans des sous-réunions existantes
        $assignedVillageIds = [];
        $subMeetings = $meeting->subMeetings()->get();
        
        foreach ($subMeetings as $subMeeting) {
            // Extraire les noms des villages du titre de la sous-réunion
            // Le titre est au format: "Titre Réunion - Village1, Village2, Village3"
            $title = $subMeeting->title;
            if (strpos($title, ' - ') !== false) {
                $villagePart = substr($title, strpos($title, ' - ') + 3);
                $villageNames = array_map('trim', explode(', ', $villagePart));
                
                // Trouver les IDs des villages par leurs noms
                foreach ($villageNames as $villageName) {
                    $village = Locality::where('name', $villageName)
                        ->whereHas('type', function($query) {
                            $query->where('name', 'village');
                        })
                        ->first();
                    
                    if ($village) {
                        $assignedVillageIds[] = $village->id;
                    }
                }
            }
        }
        $assignedVillageIds = array_unique($assignedVillageIds);

        // Log pour déboguer
        \Log::info("Villages assignés pour la réunion {$meeting->id}: " . implode(', ', $assignedVillageIds));
        \Log::info("Total villages trouvés: " . $villages->count());

        $result = [];
        foreach ($villages as $village) {
            // Exclure les villages déjà assignés
            if (in_array($village->id, $assignedVillageIds)) {
                \Log::info("Village exclu: {$village->name} (ID: {$village->id})");
                continue;
            }

            // Compter les participants attendus pour ce village
            $expectedAttendees = MeetingAttendee::where('meeting_id', $meeting->id)
                ->where('localite_id', $village->id)
                ->where('is_expected', true)
                ->count();

            $result[] = [
                'id' => $village->id,
                'name' => $village->name,
                'expected_attendees_count' => $expectedAttendees,
            ];
        }

        \Log::info("Villages disponibles retournés: " . count($result));
        return $result;
    }

    /**
     * Consolider les résultats de toutes les sous-réunions vers la réunion parent
     */
    public function consolidateResults(Meeting $parentMeeting): void
    {
        if (!$parentMeeting->isParentMeeting()) {
            throw new \Exception('Cette réunion n\'est pas une réunion principale');
        }

        $subMeetings = $parentMeeting->subMeetings()->with('attendees')->get();
        
        $totalExpected = 0;
        $totalPresent = 0;

        foreach ($subMeetings as $subMeeting) {
            $totalExpected += $subMeeting->attendees()->where('is_expected', true)->count();
            $totalPresent += $subMeeting->attendees()->where('is_present', true)->count();
        }

        $parentMeeting->update([
            'target_enrollments' => $totalExpected,
            'actual_enrollments' => $totalPresent,
        ]);
    }

    /**
     * Vérifier si toutes les sous-réunions sont terminées
     */
    public function areAllSubMeetingsCompleted(Meeting $parentMeeting): bool
    {
        return $parentMeeting->subMeetings()
            ->where('status', '!=', 'completed')
            ->count() === 0;
    }

    /**
     * Marquer la réunion parent comme terminée si toutes les sous-réunions sont terminées
     */
    public function markParentAsCompleted(Meeting $parentMeeting): void
    {
        if ($this->areAllSubMeetingsCompleted($parentMeeting)) {
            $parentMeeting->update(['status' => 'completed']);
            $this->consolidateResults($parentMeeting);
        }
    }
} 