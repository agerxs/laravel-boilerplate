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
        $query = Meeting::with(['localCommittee.locality', 'attendees'])
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
        if (!in_array($meeting->status, ['scheduled', 'prevalidated', 'validated'])) {
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
} 