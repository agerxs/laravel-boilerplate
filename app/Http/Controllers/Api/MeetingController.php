<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;
use App\Http\Utils\Constants;
use Illuminate\Support\Facades\Auth;

class MeetingController extends Controller
{
    /**
     * Liste des réunions avec filtres
     */
    public function index(Request $request)
    {
        $query = Meeting::with(['localCommittee.locality', 'attendees','attachments'])
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

        $meetings = $query->paginate($request->per_page ?? 10);

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
            'attendees',
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
            'status' => 'required|in:present,absent',
            'arrival_time' => 'required_if:status,present|nullable|date',
            'replacement_name' => 'required_if:status,absent|nullable|string|max:255',
            'replacement_phone' => 'nullable|string|max:20',
            'replacement_role' => 'nullable|string|max:255',
            'comments' => 'nullable|string'
        ]);

        $attendee = $meeting->attendees()->findOrFail($request->attendee_id);

        if ($request->status === 'present') {
            $attendee->markAsPresent($request->arrival_time);
        } else {
            $attendee->markAsAbsent();
            if ($request->replacement_name) {
                $attendee->setReplacement(
                    $request->replacement_name,
                    $request->replacement_phone,
                    $request->replacement_role
                );
            }
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
} 