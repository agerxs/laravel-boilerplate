<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;
use App\Http\Utils\Constants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MeetingController extends Controller
{
    /**
     * Liste des réunions avec filtres
     */
    public function index(Request $request)
    {
        $query = Meeting::with([
            'localCommittee.locality', 
            'attendees',
            'attachments',
            'subMeetings.attendees', // Ajouter les sous-réunions avec leurs participants
            'subMeetings.localCommittee.locality' // Ajouter les comités des sous-réunions
        ])
            ->when($request->status, function ($q, $status) {
                return $q->where('status', $status);
            })
            ->when($request->date_from, function ($q, $date) {
                return $q->whereDate('scheduled_date', '>=', $date);
            })
            ->when($request->date_to, function ($q, $date) {
                return $q->whereDate('scheduled_date', '<=', $date);
            })
            ->when($request->local_committee_id, function ($q, $id) {
                return $q->where('local_committee_id', $id);
            })
            ->orderBy('scheduled_date', 'desc');

        $meetings = $query->paginate($request->per_page ?? 30);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste des réunions récupérée avec succès', [
            'meetings' => $meetings,
            'meta' => [
                'current_page' => $meetings->currentPage(),
                'last_page' => $meetings->lastPage(),
                'per_page' => $meetings->perPage(),
                'total' => $meetings->total()
            ]
        ]);
    }

    /**
     * Détails d'une réunion
     */
    public function show(Meeting $meeting)
    {
        $meeting->load([
            'localCommittee.locality',
            'attendees.representative',
            'agenda',
            'minutes',
            'attachments',
            'enrollmentRequests'
        ]);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste des réunions récupérée avec succès', [
            'meeting' => $meeting
        ]);
    }

    /**
     * Marquer la présence d'un participant
     */
    public function markAttendance(Request $request, Meeting $meeting)
    {
        $request->validate([
            'attendee_id' => 'required|exists:meeting_attendees,id',
            'status' => 'required|in:expected,present,absent,replaced',
            'arrival_time' => 'required_if:status,present|nullable|date',
            'replacement_name' => 'required_if:status,replaced|nullable|string|max:255',
            'replacement_phone' => 'nullable|string|max:20',
            'replacement_role' => 'nullable|string|max:255',
            'comments' => 'nullable|string'
        ]);

        $attendee = $meeting->attendees()->findOrFail($request->attendee_id);

        if ($request->status === 'present') {
            $attendee->markAsPresent($request->arrival_time);
        } elseif ($request->status === 'replaced') {
            $attendee->setReplacement(
                $request->replacement_name,
                $request->replacement_phone,
                $request->replacement_role
            );
        } else {
            $attendee->markAsAbsent();
        }

        if ($request->comments) {
            $attendee->update(['comments' => $request->comments]);
        }

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste des réunions récupérée avec succès', [
            'message' => 'Présence mise à jour avec succès',
            'attendee' => $attendee
        ]);
    }

    /**
     * Finaliser la liste de présence
     */
    public function finalizeAttendance(Meeting $meeting)
    {
        if (!in_array($meeting->status, ['planned', 'prevalidated', 'validated'])) {
            return response()->json([
                'message' => 'Cette réunion ne peut pas être finalisée'
            ], 403);
        }

        $meeting->attendees()
            ->whereNull('attendance_status')
            ->update(['attendance_status' => 'absent', 'is_present' => false]);

        $meeting->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => Auth::id()
        ]);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste de présence finalisée avec succès', [
           
        ]);
    }

    /**
     * Mettre à jour les enrôlements
     */
    public function updateEnrollments(Request $request, Meeting $meeting)
    {
        $request->validate([
            'target_enrollments' => 'required|integer|min:0',
            'actual_enrollments' => 'required|integer|min:0'
        ]);

        $meeting->update([
            'target_enrollments' => $request->target_enrollments,
            'actual_enrollments' => $request->actual_enrollments
        ]);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Enrôlements mis à jour avec succès', [
            'meeting' => $meeting
        ]);
    }

    /**
     * Créer une réunion (pour la synchronisation mobile)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'scheduledDate' => 'required|date',
            'location' => 'required|string|max:255',
            'status' => 'required|string',
            'localCommitteeId' => 'nullable|integer|exists:local_committees,id',
            'attendees' => 'nullable|array',
            'attendees.*.name' => 'required|string|max:255',
            'attendees.*.phone' => 'required|string|max:20',
            'attendees.*.role' => 'required|string|max:255',
            'attendees.*.comments' => 'nullable|string',
            'attendees.*.presence_photo' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*.file_path' => 'required|string',
            'attachments.*.original_name' => 'required|string',
            'attachments.*.file_type' => 'required|string',
            'attachments.*.title' => 'required|string',
            'attachments.*.nature' => 'required|string',
            'attachments.*.size' => 'required|integer',
        ]);

        $validated['local_committee_id'] = $validated['localCommitteeId'];
        $validated['scheduled_date'] = $validated['scheduledDate'];
        unset($validated['localCommitteeId']);
        unset($validated['scheduledDate']);
        $attendees = $validated['attendees'] ?? null;
        $attachments = $validated['attachments'] ?? null;
        unset($validated['attendees']);
        unset($validated['attachments']);

        $meeting = Meeting::create($validated);

        // Création des attendees si fournis
        if ($attendees && is_array($attendees)) {
            foreach ($attendees as $attendeeData) {
                $attendeeData['meeting_id'] = $meeting->id;
                $attendee = \App\Models\MeetingAttendee::create($attendeeData);

                // Gérer la photo de présence si fournie
                if (isset($attendeeData['presence_photo'])) {
                    $photoPath = $attendeeData['presence_photo'];
                    if (file_exists($photoPath)) {
                        $photoName = 'attendance_' . $attendee->id . '_' . time() . '.jpg';
                        $destinationPath = storage_path('app/public/attendance_photos');
                        if (!file_exists($destinationPath)) {
                            mkdir($destinationPath, 0755, true);
                        }
                        copy($photoPath, $destinationPath . '/' . $photoName);
                        $attendee->update(['presence_photo' => 'attendance_photos/' . $photoName]);
                    }
                }
            }
        }

        // Gérer les pièces jointes si fournies
        if ($attachments && is_array($attachments)) {
            foreach ($attachments as $attachmentData) {
                $filePath = $attachmentData['file_path'];
                if (file_exists($filePath)) {
                    // Créer le nom du fichier avec le titre et l'horodatage
                    $extension = pathinfo($attachmentData['original_name'], PATHINFO_EXTENSION);
                    $timestamp = now()->format('Y-m-d_His');
                    $sanitizedTitle = \Illuminate\Support\Str::slug($attachmentData['title']);
                    $filename = "{$sanitizedTitle}_{$timestamp}.{$extension}";

                    // Vérifier que le dossier existe
                    if (!Storage::disk('public')->exists('attachments')) {
                        Storage::disk('public')->makeDirectory('attachments');
                    }

                    // Copier le fichier
                    $path = Storage::disk('public')->putFileAs(
                        'attachments',
                        $filePath,
                        $filename
                    );

                    if ($path) {
                        $meeting->attachments()->create([
                            'title' => $attachmentData['title'],
                            'original_name' => $attachmentData['original_name'],
                            'file_path' => $path,
                            'file_type' => $attachmentData['file_type'],
                            'nature' => $attachmentData['nature'],
                            'size' => $attachmentData['size'],
                            'uploaded_by' => Auth::id(),
                        ]);
                    }
                }
            }
        }

        return $this->format(Constants::JSON_STATUS_SUCCESS, 201, 'Réunion ajoutée avec succès', [
            'meeting' => $meeting->load(['attendees', 'localCommittee.locality', 'attachments'])
        ]);
    }

    /**
     * Mettre à jour une réunion (pour la synchronisation mobile)
     */
    public function update(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'scheduledDate' => 'sometimes|required|date',
            'location' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|string',
            'localCommitteeId' => 'nullable|integer|exists:local_committees,id',
            // Ajoute ici les autres champs nécessaires
            'attendees' => 'nullable|array',
        ]);
        if (isset($validated['localCommitteeId'])) {
            $validated['local_committee_id'] = $validated['localCommitteeId'];
            unset($validated['localCommitteeId']);
        }
        if (isset($validated['scheduledDate'])) {
            $validated['scheduled_date'] = $validated['scheduledDate'];
            unset($validated['scheduledDate']);
        }
        if (isset($validated['meetingId'])) {
            $validated['meeting_id'] = $validated['meetingId'];
            unset($validated['meetingId']);
        }
        $attendees = $validated['attendees'] ?? null;
        unset($validated['attendees']);
        
        $meeting->update($validated);

        // Met à jour les attendees si fournis
        if ($attendees && is_array($attendees)) {
            foreach ($attendees as $attendeeData) {
                if (array_key_exists('meetingId',$attendeeData)) {
                    $attendeeData['meeting_id'] = $attendeeData['meetingId'];
                    unset($attendeeData['meetingId']);
                }
                if (array_key_exists('remoteId', $attendeeData)) {
                    unset($attendeeData['remoteId']);
                }
                if (array_key_exists('remote_id', $attendeeData)) {
                    unset($attendeeData['remote_id']);
                }
                if (array_key_exists('localMeetingId', $attendeeData)) {
                    unset($attendeeData['localMeetingId']);
                }
                if (array_key_exists('localityId',$attendeeData)) {
                    $attendeeData['localite_id'] = $attendeeData['localityId'];
                    unset($attendeeData['localityId']);
                }
                if (array_key_exists('representativeId',$attendeeData)) {
                    $attendeeData['representative_id'] = $attendeeData['representativeId'];
                    unset($attendeeData['representativeId']);
                }
                if (array_key_exists('attendanceStatus',$attendeeData)) {
                    $attendeeData['attendance_status'] = $attendeeData['attendanceStatus'];
                    unset($attendeeData['attendanceStatus']);
                }
                if (array_key_exists('replacementName',$attendeeData)) {
                    $attendeeData['replacement_name'] = $attendeeData['replacementName'];
                    unset($attendeeData['replacementName']);
                }
                if (array_key_exists('replacementPhone',$attendeeData)) {
                    $attendeeData['replacement_phone'] = $attendeeData['replacementPhone'];
                    unset($attendeeData['replacementPhone']);
                }
                if (array_key_exists('replacementRole',$attendeeData)) {
                    $attendeeData['replacement_role'] = $attendeeData['replacementRole'];
                    unset($attendeeData['replacementRole']);
                }
                if (array_key_exists('arrivalTime',$attendeeData)) {
                    unset($attendeeData['arrivalTime']);
                }
                if (array_key_exists('paymentStatus',$attendeeData)) {
                    unset($attendeeData['paymentStatus']);
                }
                if (array_key_exists('presenceTimestamp',$attendeeData)) {
                    unset($attendeeData['presenceTimestamp']);
                }
                if (array_key_exists('presencePhoto',$attendeeData)) {
                    $attendeeData['presence_photo'] = $attendeeData['presencePhoto'];
                    unset($attendeeData['presencePhoto']);
                }
                if (array_key_exists('presenceLocation',$attendeeData)) {
                    $attendeeData['presence_location'] = $attendeeData['presenceLocation'];
                    unset($attendeeData['presenceLocation']);
                }

                if (array_key_exists('isExpected',$attendeeData)) {
                    $attendeeData['is_expected'] = $attendeeData['isExpected'];
                    unset($attendeeData['isExpected']);
                }
                if (array_key_exists('isPresent',$attendeeData)) {
                    $attendeeData['is_present'] = $attendeeData['isPresent'];
                    unset($attendeeData['isPresent']);
                }
                if (array_key_exists('createdAt',$attendeeData)) {
                    //TODO: $attendeeData['created_at'] = $attendeeData['createdAt'];
                    unset($attendeeData['createdAt']);
                }
                if (array_key_exists('updatedAt',$attendeeData)) {
                    //$attendeeData['updated_at'] = $attendeeData['updatedAt'];
                    unset($attendeeData['updatedAt']);
                }
                if (array_key_exists('deletedAt',$attendeeData)) {
                    //$attendeeData['deleted_at'] = $attendeeData['deletedAt'];
                    unset($attendeeData['deletedAt']);
                }
                if (array_key_exists('lastModified',$attendeeData)) {
                    //$attendeeData['last_modified'] = $attendeeData['lastModified'];
                    unset($attendeeData['lastModified']);
                }
                if (array_key_exists('isSynced',$attendeeData)) {
                    unset($attendeeData['isSynced']);
                }
                if (array_key_exists('isDirty',$attendeeData)) {
                    unset($attendeeData['isDirty']);
                }
                
               
              

                if (isset($attendeeData['id'])) {
                    // Mise à jour d'un participant existant
                    \App\Models\MeetingAttendee::where('id', $attendeeData['id'])
                        ->update($attendeeData);
                } else {
                    // Création d'un nouveau participant
                    $attendeeData['meeting_id'] = $meeting->id;
                    \App\Models\MeetingAttendee::create($attendeeData);
                }
            }
        }

        return $this->format(\App\Http\Utils\Constants::JSON_STATUS_SUCCESS, 200, 'Réunion mise à jour avec succès', [
            'meeting' => $meeting
        ]);
    }

    /**
     * Confirmer une réunion (pour les secrétaires)
     */
    public function confirm(Meeting $meeting)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['secretaire', 'admin'])) {
            return $this->format(Constants::JSON_STATUS_ERROR, 403, 'Accès non autorisé');
        }

        $meeting->update([
            'status' => 'completed',
            'confirmed_at' => now(),
            'confirmed_by' => Auth::id(),
        ]);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Réunion confirmée avec succès', [
            'meeting' => $meeting->fresh()
        ]);
    }


    /**
     * Valider une réunion (pour les sous-préfets)
     */
    public function validateMeeting(Meeting $meeting)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['sous_prefet', 'admin'])) {
            return $this->format(Constants::JSON_STATUS_ERROR, 403, 'Accès non autorisé');
        }

        $meeting->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => Auth::id(),
        ]);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Réunion validée avec succès', [
            'meeting' => $meeting->fresh()
        ]);
    }

    /**
     * Annuler une réunion
     */
    public function cancel(Meeting $meeting)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['secretaire', 'admin'])) {
            return $this->format(Constants::JSON_STATUS_ERROR, 403, 'Accès non autorisé');
        }

        $meeting->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
        ]);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Réunion annulée avec succès', [
            'meeting' => $meeting->fresh()
        ]);
    }

    /**
     * Reporter une réunion
     */
    public function reschedule(Request $request, Meeting $meeting)
    {
        $request->validate([
            'scheduled_date' => 'required|date|after:now',
            'reason' => 'nullable|string|max:500',
        ]);

        $meeting->update([
            'scheduled_date' => $request->scheduled_date,
            'rescheduled_at' => now(),
            'rescheduled_by' => Auth::id(),
            'reschedule_reason' => $request->reason,
        ]);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Réunion reportée avec succès', [
            'meeting' => $meeting->fresh()
        ]);
    }

    /**
     * Finaliser une réunion
     */
    public function finalize(Meeting $meeting)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['secretaire', 'admin'])) {
            return $this->format(Constants::JSON_STATUS_ERROR, 403, 'Accès non autorisé');
        }

        $meeting->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => Auth::id(),
        ]);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Réunion finalisée avec succès', [
            'meeting' => $meeting->fresh()
        ]);
    }

    /**
     * Soumettre une liste de présence
     */
    public function submitAttendanceList(Request $request, Meeting $meeting)
    {
        info('=== SOUMISSION SANS PHOTOS DÉBUT ===');
        info('Meeting ID: ' . $meeting->id);
        info('User ID: ' . Auth::id());
        info('Request method: ' . $request->method());
        info('Request URL: ' . $request->fullUrl());
        info('Toutes les données reçues: ' . json_encode($request->all()));
        
        $request->validate([
            'attendances' => 'required|array',
            'attendances.*.attendee_id' => 'required|exists:meeting_attendees,id',
            'attendances.*.status' => 'required|in:expected,present,absent,replaced',
            'attendances.*.comments' => 'nullable|string',
        ]);

        info('Validation passée avec succès');

        foreach ($request->attendances as $index => $attendanceData) {
            info("=== TRAITEMENT ATTENDANCE $index ===");
            info('Données attendance: ' . json_encode($attendanceData));
            
            $attendee = $meeting->attendees()->with('representative')->findOrFail($attendanceData['attendee_id']);
            info('Attendee trouvé: ID ' . $attendee->id . ', Nom: ' . $attendee->name);
            
            $attendee->update([
                'attendance_status' => $attendanceData['status'],
                'comments' => $attendanceData['comments'] ?? null,
                'submitted_at' => now(),
                'submitted_by' => Auth::id(),
            ]);
            
            info('Attendee mis à jour avec succès');
        }

        // Mettre à jour le statut de la liste de présence du meeting
        try {
            $meeting->update([
                'attendance_status' => 'submitted',
                'attendance_submitted_at' => now(),
                'attendance_submitted_by' => Auth::id(),
            ]);
            info('Statut de la liste de présence du meeting mis à jour: submitted');
        } catch (\Exception $e) {
            info('Erreur lors de la mise à jour du statut du meeting: ' . $e->getMessage());
        }

        info('=== SOUMISSION SANS PHOTOS TERMINÉE ===');
        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste de présence soumise avec succès');
    }

    /**
     * Soumettre une liste de présence avec photos
     */
    public function submitAttendanceListWithPhotos(Request $request, Meeting $meeting)
    {
        info('=== SOUMISSION AVEC PHOTOS DÉBUT ===');
        info('Meeting ID: ' . $meeting->id);
        info('User ID: ' . Auth::id());
        info('Request method: ' . $request->method());
        info('Request URL: ' . $request->fullUrl());
        
        // Log des headers
        info('Content-Type: ' . $request->header('Content-Type'));
        info('Content-Length: ' . $request->header('Content-Length'));
        
        // Log des données reçues
        info('Toutes les données reçues: ' . json_encode($request->all()));
        info('Fichiers reçus: ' . json_encode($request->allFiles()));
        
        $request->validate([
            'attendances' => 'required|string', // JSON string
            'photos.*' => 'nullable|image|max:10240', // Max 10MB par photo
        ]);

        info('Validation passée avec succès');

        // Décoder les données JSON des attendances
        $attendancesData = json_decode($request->attendances, true);
        
        info('Données JSON décodées: ' . json_encode($attendancesData));
        
        if (!$attendancesData || !is_array($attendancesData)) {
            info('Format des données invalide');
            return $this->format(Constants::JSON_STATUS_ERROR, 422, 'Format des données invalide');
        }

        info('Nombre d\'attendances à traiter: ' . count($attendancesData));

        // Valider chaque attendance
        foreach ($attendancesData as $index => $attendanceData) {
            info("=== TRAITEMENT ATTENDANCE $index ===");
            info('Données attendance: ' . json_encode($attendanceData));
            
            if (!isset($attendanceData['attendee_id']) || !isset($attendanceData['status'])) {
                info('Données d\'attendance incomplètes pour index ' . $index);
                return $this->format(Constants::JSON_STATUS_ERROR, 422, 'Données d\'attendance incomplètes');
            }
            
            $attendee = $meeting->attendees()->with('representative')->findOrFail($attendanceData['attendee_id']);
            info('Attendee trouvé: ID ' . $attendee->id . ', Nom: ' . $attendee->name);
            
            // Préparer les données de mise à jour
            $updateData = [
                'attendance_status' => $attendanceData['status'],
                'comments' => $attendanceData['comments'] ?? null,
                'submitted_at' => now(),
                'submitted_by' => Auth::id(),
            ];

            // Ajouter les données de géolocalisation si disponibles
            if (isset($attendanceData['presence_location']) && is_array($attendanceData['presence_location'])) {
                $updateData['presence_location'] = $attendanceData['presence_location'];
                info('Géolocalisation ajoutée: ' . json_encode($attendanceData['presence_location']));
            }
            
            if (isset($attendanceData['presence_timestamp'])) {
                $updateData['presence_timestamp'] = $attendanceData['presence_timestamp'];
                info('Timestamp ajouté: ' . $attendanceData['presence_timestamp']);
            }

            // Vérifier et traiter la photo
            info('Has photo flag: ' . ($attendanceData['has_photo'] ?? 'non défini'));
            info('Tous les fichiers reçus: ' . json_encode($request->allFiles()));
            
            $photoFound = false;
            
            // Essayer de trouver la photo avec différentes approches
            $photo = null;
            
            // Méthode 1: Essayer avec la clé indexée
            $photoKey = "photos[$index]";
            info('Recherche de photo avec clé: ' . $photoKey);
            
            if ($request->hasFile($photoKey)) {
                $photo = $request->file($photoKey);
                info('Photo trouvée avec clé indexée: ' . $photoKey);
            } else {
                // Méthode 2: Essayer d'accéder directement au tableau photos
                $allFiles = $request->allFiles();
                if (isset($allFiles['photos']) && is_array($allFiles['photos']) && isset($allFiles['photos'][$index])) {
                    $photo = $allFiles['photos'][$index];
                    info('Photo trouvée dans le tableau photos à l\'index ' . $index);
                } else {
                    info('Aucune photo trouvée avec les méthodes standard');
                }
            }
            
            if ($photo) {
                info('Photo trouvée pour ' . $attendee->name);
                info('Nom original: ' . $photo->getClientOriginalName());
                info('Taille: ' . $photo->getSize() . ' bytes');
                info('Type MIME: ' . $photo->getMimeType());
                info('Extension: ' . $photo->getClientOriginalExtension());
                
                try {
                    // Créer le dossier s'il n'existe pas
                    $storagePath = storage_path('app/public/presence-photos');
                    if (!file_exists($storagePath)) {
                        mkdir($storagePath, 0755, true);
                        info('Dossier créé: ' . $storagePath);
                    }
                    
                    $photoPath = $photo->store('presence-photos', 'public');
                    $updateData['presence_photo'] = $photoPath;
                    info('Photo sauvegardée avec succès: ' . $photoPath);
                    $photoFound = true;
                } catch (\Exception $e) {
                    info('Erreur lors de la sauvegarde de la photo: ' . $e->getMessage());
                }
            } else {
                info('Aucune photo trouvée pour cet attendance');
            }
            
            if (!$photoFound) {
                info('Aucune photo trouvée pour cet attendance avec aucune des clés');
            }
            
            if (!$photoFound) {
                info('Aucune photo trouvée pour cet attendance');
            }

            info('Données de mise à jour: ' . json_encode($updateData));
            
            try {
                $attendee->update($updateData);
                info('Attendee mis à jour avec succès');
            } catch (\Exception $e) {
                info('Erreur lors de la mise à jour de l\'attendee: ' . $e->getMessage());
            }
        }

        // Mettre à jour le statut de la liste de présence du meeting
        try {
            $meeting->update([
                'attendance_status' => 'submitted',
                'attendance_submitted_at' => now(),
                'attendance_submitted_by' => Auth::id(),
            ]);
            info('Statut de la liste de présence du meeting mis à jour: submitted');
        } catch (\Exception $e) {
            info('Erreur lors de la mise à jour du statut du meeting: ' . $e->getMessage());
        }

        info('=== SOUMISSION AVEC PHOTOS TERMINÉE ===');
        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste de présence avec photos soumise avec succès');
    }

    /**
     * Valider une liste de présence (pour les sous-préfets)
     */
    public function validateAttendance(Request $request, Meeting $meeting)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['sous_prefet', 'admin'])) {
            return $this->format(Constants::JSON_STATUS_ERROR, 403, 'Accès non autorisé');
        }

        $meeting->update([
            'attendance_status' => 'validated',
            'attendance_validated_at' => now(),
            'attendance_validated_by' => Auth::id(),
        ]);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste de présence validée avec succès');
    }

    /**
     * Rejeter une liste de présence
     */
    public function rejectAttendanceList(Request $request, Meeting $meeting)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['sous_prefet', 'admin'])) {
            return $this->format(Constants::JSON_STATUS_ERROR, 403, 'Accès non autorisé');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $meeting->update([
            'attendance_status' => 'rejected',
            'attendance_rejected_at' => now(),
            'attendance_rejected_by' => Auth::id(),
            'attendance_rejection_reason' => $request->reason,
        ]);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste de présence rejetée');
    }

    /**
     * Exporter la liste de présence en PDF
     */
    public function exportAttendancePDF(Meeting $meeting)
    {
        // TODO: Implémenter l'export PDF
        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Export PDF', [
            'pdf_url' => '/exports/attendance-' . $meeting->id . '.pdf'
        ]);
    }

    /**
     * Soumettre une réunion complète depuis l'application mobile
     * Inclut toutes les données : réunion, présences, minutes, pièces jointes
     */
    public function submitCompleteMeeting(Request $request, Meeting $meeting)
    {
        info('=== SOUMISSION COMPLÈTE DE RÉUNION DÉBUT ===');
        info('Meeting ID: ' . $meeting->id);
        info('User ID: ' . Auth::id());
        info('Request method: ' . $request->method());
        info('Request URL: ' . $request->fullUrl());
        info('Toutes les données reçues: ' . json_encode($request->all()));
        
        $request->validate([
            'meeting' => 'required|array',
            'meeting.title' => 'nullable|string|max:255',
            'meeting.description' => 'nullable|string',
            'meeting.agenda' => 'nullable|string',
            'meeting.difficulties' => 'nullable|string',
            'meeting.recommendations' => 'nullable|string',
            'meeting.start_datetime' => 'nullable|date',
            'meeting.end_datetime' => 'nullable|date',
            'attendances' => 'nullable|array',
            'attendances.*.representative_id' => 'nullable|exists:representatives,id',
            'attendances.*.is_expected' => 'boolean',
            'attendances.*.is_present' => 'boolean',
            'attendances.*.comments' => 'nullable|string',
            'attendances.*.arrival_time' => 'nullable|date',
            'attendances.*.name' => 'nullable|string|max:255',
            'attendances.*.phone' => 'nullable|string|max:20',
            'attendances.*.role' => 'nullable|string|max:255',
            'attendances.*.attendance_status' => 'nullable|string|in:expected,present,absent,replaced',
            'minutes' => 'nullable|array',
            'minutes.content' => 'nullable|string',
            'minutes.status' => 'nullable|string|in:draft,published,pending_validation,validated',
            'minutes.difficulties' => 'nullable|string',
            'minutes.recommendations' => 'nullable|string',
            'minutes.people_to_enroll_count' => 'nullable|integer',
            'minutes.people_enrolled_count' => 'nullable|integer',
            'minutes.cmu_cards_available_count' => 'nullable|integer',
            'minutes.cmu_cards_distributed_count' => 'nullable|integer',
            'minutes.complaints_received_count' => 'nullable|integer',
            'minutes.complaints_processed_count' => 'nullable|integer',
            'attachments' => 'nullable|array',
            'attachments.*.title' => 'nullable|string|max:255',
            'attachments.*.original_name' => 'nullable|string|max:255',
            'attachments.*.file_path' => 'nullable|string',
            'attachments.*.file_type' => 'nullable|string|max:100',
            'attachments.*.nature' => 'nullable|string|in:photo,document_justificatif,compte_rendu',
            'attachments.*.size' => 'nullable|integer',
            'attachments.*.uploaded_by' => 'nullable|integer|exists:users,id',
            'submitted_at' => 'nullable|date',
            'submitted_by' => 'nullable|integer|exists:users,id',
        ]);

        info('Validation passée avec succès');

        try {
            DB::beginTransaction();

            // 1. Mettre à jour les informations de la réunion
            if ($request->has('meeting')) {
                $meetingData = $request->meeting;
                $updateData = [];
                
                if (isset($meetingData['title'])) $updateData['title'] = $meetingData['title'];
                if (isset($meetingData['description'])) $updateData['description'] = $meetingData['description'];
                if (isset($meetingData['agenda'])) $updateData['agenda'] = $meetingData['agenda'];
                if (isset($meetingData['difficulties'])) $updateData['difficulties'] = $meetingData['difficulties'];
                if (isset($meetingData['recommendations'])) $updateData['recommendations'] = $meetingData['recommendations'];
                if (isset($meetingData['start_datetime'])) $updateData['start_datetime'] = $meetingData['start_datetime'];
                if (isset($meetingData['end_datetime'])) $updateData['end_datetime'] = $meetingData['end_datetime'];
                
                if (!empty($updateData)) {
                    $meeting->update($updateData);
                    info('Informations de la réunion mises à jour');
                }
            }

            // 2. Traiter les présences
            if ($request->has('attendances') && is_array($request->attendances)) {
                foreach ($request->attendances as $index => $attendanceData) {
                    info("=== TRAITEMENT ATTENDANCE $index ===");
                    info('Données attendance: ' . json_encode($attendanceData));
                    
                    // Trouver ou créer l'attendee
                    $attendee = null;
                    if (isset($attendanceData['representative_id'])) {
                        $attendee = $meeting->attendees()->where('representative_id', $attendanceData['representative_id'])->first();
                    }
                    
                    if (!$attendee && isset($attendanceData['name'])) {
                        // Créer un nouvel attendee si pas trouvé
                        $attendee = $meeting->attendees()->create([
                            'name' => $attendanceData['name'],
                            'phone' => $attendanceData['phone'] ?? '',
                            'role' => $attendanceData['role'] ?? '',
                            'representative_id' => $attendanceData['representative_id'] ?? null,
                            'attendance_status' => $attendanceData['attendance_status'] ?? 'expected',
                        ]);
                    }
                    
                    if ($attendee) {
                        $updateData = [];
                        if (isset($attendanceData['is_expected'])) $updateData['is_expected'] = $attendanceData['is_expected'];
                        if (isset($attendanceData['is_present'])) $updateData['is_present'] = $attendanceData['is_present'];
                        if (isset($attendanceData['comments'])) $updateData['comments'] = $attendanceData['comments'];
                        if (isset($attendanceData['arrival_time'])) $updateData['arrival_time'] = $attendanceData['arrival_time'];
                        if (isset($attendanceData['attendance_status'])) $updateData['attendance_status'] = $attendanceData['attendance_status'];
                        
                        if (!empty($updateData)) {
                            $attendee->update($updateData);
                            info('Attendee mis à jour avec succès');
                        }
                    }
                }
            }

            // 3. Traiter les minutes
            if ($request->has('minutes') && is_array($request->minutes)) {
                $minutesData = $request->minutes;
                
                $minutes = $meeting->minutes()->first();
                if (!$minutes) {
                    $minutes = $meeting->minutes()->create([
                        'content' => $minutesData['content'] ?? null,
                        'status' => $minutesData['status'] ?? 'draft',
                        'difficulties' => $minutesData['difficulties'] ?? null,
                        'recommendations' => $minutesData['recommendations'] ?? null,
                        'people_to_enroll_count' => $minutesData['people_to_enroll_count'] ?? null,
                        'people_enrolled_count' => $minutesData['people_enrolled_count'] ?? null,
                        'cmu_cards_available_count' => $minutesData['cmu_cards_available_count'] ?? null,
                        'cmu_cards_distributed_count' => $minutesData['cmu_cards_distributed_count'] ?? null,
                        'complaints_received_count' => $minutesData['complaints_received_count'] ?? null,
                        'complaints_processed_count' => $minutesData['complaints_processed_count'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $minutes->update([
                        'content' => $minutesData['content'] ?? $minutes->content,
                        'status' => $minutesData['status'] ?? $minutes->status,
                        'difficulties' => $minutesData['difficulties'] ?? $minutes->difficulties,
                        'recommendations' => $minutesData['recommendations'] ?? $minutes->recommendations,
                        'people_to_enroll_count' => $minutesData['people_to_enroll_count'] ?? $minutes->people_to_enroll_count,
                        'people_enrolled_count' => $minutesData['people_enrolled_count'] ?? $minutes->people_enrolled_count,
                        'cmu_cards_available_count' => $minutesData['cmu_cards_available_count'] ?? $minutes->cmu_cards_available_count,
                        'cmu_cards_distributed_count' => $minutesData['cmu_cards_distributed_count'] ?? $minutes->cmu_cards_distributed_count,
                        'complaints_received_count' => $minutesData['complaints_received_count'] ?? $minutes->complaints_received_count,
                        'complaints_processed_count' => $minutesData['complaints_processed_count'] ?? $minutes->complaints_processed_count,
                        'updated_at' => now(),
                    ]);
                }
                info('Minutes traitées avec succès');
            }

            // 4. Traiter les pièces jointes
            if ($request->has('attachments') && is_array($request->attachments)) {
                foreach ($request->attachments as $index => $attachmentData) {
                    info("=== TRAITEMENT ATTACHMENT $index ===");
                    info('Données attachment: ' . json_encode($attachmentData));
                    
                    // Vérifier si le fichier existe localement
                    if (isset($attachmentData['file_path']) && Storage::exists($attachmentData['file_path'])) {
                        $attachment = $meeting->attachments()->create([
                            'title' => $attachmentData['title'] ?? 'Pièce jointe',
                            'original_name' => $attachmentData['original_name'] ?? 'document',
                            'file_path' => $attachmentData['file_path'],
                            'file_type' => $attachmentData['file_type'] ?? 'application/octet-stream',
                            'nature' => $attachmentData['nature'] ?? 'document_justificatif',
                            'size' => $attachmentData['size'] ?? 0,
                            'uploaded_by' => $attachmentData['uploaded_by'] ?? Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        info('Attachment créé avec succès');
                    }
                }
            }

            // 5. Mettre à jour le statut de soumission de la réunion
            $meeting->update([
                'attendance_status' => 'submitted',
                'attendance_submitted_at' => $request->submitted_at ?? now(),
                'attendance_submitted_by' => $request->submitted_by ?? Auth::id(),
                'updated_at' => now(),
            ]);

            DB::commit();
            info('=== SOUMISSION COMPLÈTE DE RÉUNION TERMINÉE AVEC SUCCÈS ===');
            
            return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Réunion soumise avec succès. Toutes les données ont été enregistrées.', [
                'meeting_id' => $meeting->id,
                'submitted_at' => now()->toISOString(),
                'submitted_by' => Auth::id(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            info('=== ERREUR LORS DE LA SOUMISSION COMPLÈTE ===');
            info('Erreur: ' . $e->getMessage());
            info('Stack trace: ' . $e->getTraceAsString());
            
            return $this->format(Constants::JSON_STATUS_ERROR, 500, 'Erreur lors de la soumission de la réunion: ' . $e->getMessage());
        }
    }
} 