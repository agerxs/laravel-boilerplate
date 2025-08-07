<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Services\MeetingSplitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Utils\Constants;

class MeetingSplitController extends Controller
{
    protected $meetingSplitService;

    public function __construct(MeetingSplitService $meetingSplitService)
    {
        $this->meetingSplitService = $meetingSplitService;
    }

    /**
     * Obtenir les villages disponibles pour l'éclatement d'une réunion
     */
    public function getAvailableVillages(Meeting $meeting): JsonResponse
    {
        try {
            if (!$meeting->canBeSplit()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réunion ne peut pas être éclatée'
                ], 400);
            }

            $villages = $this->meetingSplitService->getAvailableVillages($meeting);

            return response()->json([
                'success' => true,
                'message' => 'Villages récupérés avec succès',
                'data' => [
                    'villages' => $villages,
                    'meeting' => $meeting->load(['localCommittee.locality'])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des villages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Éclater une réunion en sous-réunions
     */
    public function splitMeeting(Request $request, Meeting $meeting): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sub_meetings' => 'required|array|min:1',
                'sub_meetings.*.location' => 'required|string',
                'sub_meetings.*.villages' => 'required|array|min:1',
                'sub_meetings.*.villages.*.id' => 'required|exists:localite,id',
                'sub_meetings.*.villages.*.name' => 'required|string',
            ]);

            if (!$meeting->canBeSplit()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réunion ne peut pas être éclatée'
                ], 400);
            }

            $subMeetings = $this->meetingSplitService->splitMeeting($meeting, $validated['sub_meetings']);

            return response()->json([
                'success' => true,
                'message' => 'Réunion éclatée avec succès en ' . count($subMeetings) . ' sous-réunions',
                'data' => [
                    'parent_meeting' => $meeting->load(['subMeetings.attendees']),
                    'sub_meetings' => $subMeetings
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'éclatement de la réunion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consolider les résultats des sous-réunions vers la réunion parent
     */
    public function consolidateResults(Meeting $meeting): JsonResponse
    {
        try {
            if (!$meeting->isParentMeeting()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réunion n\'est pas une réunion principale'
                ], 400);
            }

            $this->meetingSplitService->consolidateResults($meeting);

            return response()->json([
                'success' => true,
                'message' => 'Résultats consolidés avec succès',
                'data' => [
                    'meeting' => $meeting->load(['subMeetings.attendees'])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la consolidation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les détails d'une réunion avec ses sous-réunions
     */
    public function getMeetingWithSubMeetings(Meeting $meeting): JsonResponse
    {
        try {
            $meeting->load([
                'subMeetings.attendees.locality',
                'subMeetings.attendees.representative',
                'localCommittee.locality'
            ]);

            $data = [
                'meeting' => $meeting,
                'is_parent' => $meeting->isParentMeeting(),
                'is_sub' => $meeting->isSubMeeting(),
                'can_be_split' => $meeting->canBeSplit(),
                'total_expected_attendees' => $meeting->getTotalExpectedAttendees(),
                'total_present_attendees' => $meeting->getTotalPresentAttendees(),
                'all_sub_meetings_completed' => $meeting->areAllSubMeetingsCompleted(),
            ];

            if ($meeting->isParentMeeting()) {
                $data['sub_meetings_count'] = $meeting->subMeetings()->count();
            }

            if ($meeting->isSubMeeting()) {
                $data['parent_meeting'] = $meeting->parentMeeting;
            }

            return response()->json([
                'success' => true,
                'message' => 'Détails de la réunion récupérés avec succès',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails: ' . $e->getMessage()
            ], 500);
        }
    }
} 