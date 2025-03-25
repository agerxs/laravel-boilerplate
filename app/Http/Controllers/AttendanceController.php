<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AttendanceController extends Controller
{
    /**
     * Affiche l'interface de gestion de la liste de présence pour une réunion.
     */
    public function index(Meeting $meeting)
    {
        // Vérifier si la réunion est déjà terminée
        $isCompleted = $meeting->status === 'completed';
        
        // Vérifier que la réunion est dans un état qui permet la gestion des présences
        $allowedStatuses = ['planned', 'scheduled', 'validated', 'completed'];
        if (!in_array($meeting->status, $allowedStatuses)) {
            return redirect()->back()->with('error', 'La gestion des présences n\'est pas disponible pour cette réunion.');
        }
        
        // Récupérer les participants
        $attendees = $meeting->attendees()
            ->with('village')
            ->get()
            ->map(function ($attendee) {
                return [
                    'id' => $attendee->id,
                    'name' => $attendee->name,
                    'phone' => $attendee->phone,
                    'role' => $attendee->role,
                    'village' => [
                        'id' => $attendee->localite_id,
                        'name' => $attendee->village ? $attendee->village->name : ($attendee->localite_id ? 'Village à identifier' : 'Pas de village associé')
                    ],
                    'is_expected' => $attendee->is_expected,
                    'is_present' => $attendee->is_present,
                    'attendance_status' => $attendee->attendance_status,
                    'replacement_name' => $attendee->replacement_name,
                    'replacement_phone' => $attendee->replacement_phone,
                    'replacement_role' => $attendee->replacement_role,
                    'arrival_time' => $attendee->arrival_time,
                    'comments' => $attendee->comments,
                    'payment_status' => $attendee->payment_status
                ];
            });
        
        return Inertia::render('Meetings/Attendance', [
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'scheduled_date' => $meeting->scheduled_date,
                'location' => $meeting->location ?? $meeting->localCommittee->locality->name ?? 'Non défini',
                'local_committee' => [
                    'id' => $meeting->localCommittee->id ?? null,
                    'name' => $meeting->localCommittee->name ?? 'Non défini'
                ],
                'status' => $meeting->status,
                'is_completed' => $isCompleted
            ],
            'attendees' => $attendees
        ]);
    }

    /**
     * Marquer un participant comme présent.
     */
    public function markPresent(Request $request, MeetingAttendee $attendee)
    {
        $request->validate([
            'arrival_time' => 'nullable|date',
        ]);

        $arrivalTime = $request->input('arrival_time') 
            ? Carbon::parse($request->input('arrival_time')) 
            : now();

        $attendee->markAsPresent($arrivalTime);

        return response()->json([
            'message' => 'Participant marqué comme présent',
            'attendee' => $attendee
        ]);
    }

    /**
     * Marquer un participant comme absent.
     */
    public function markAbsent(MeetingAttendee $attendee)
    {
        $attendee->markAsAbsent();

        return response()->json([
            'message' => 'Participant marqué comme absent',
            'attendee' => $attendee
        ]);
    }

    /**
     * Enregistrer un remplaçant pour un participant.
     */
    public function setReplacement(Request $request, MeetingAttendee $attendee)
    {
        $request->validate([
            'replacement_name' => 'required|string|max:255',
            'replacement_phone' => 'nullable|string|max:20',
            'replacement_role' => 'nullable|string|max:255',
        ]);

        $attendee->setReplacement(
            $request->input('replacement_name'),
            $request->input('replacement_phone'),
            $request->input('replacement_role')
        );

        return response()->json([
            'message' => 'Remplaçant enregistré avec succès',
            'attendee' => $attendee
        ]);
    }

    /**
     * Ajouter un commentaire pour un participant.
     */
    public function addComment(Request $request, MeetingAttendee $attendee)
    {
        $request->validate([
            'comments' => 'required|string',
        ]);

        $attendee->update([
            'comments' => $request->input('comments')
        ]);

        return response()->json([
            'message' => 'Commentaire ajouté avec succès',
            'attendee' => $attendee
        ]);
    }

    /**
     * Finaliser la liste de présence et marquer la réunion comme terminée
     */
    public function finalize(Meeting $meeting)
    {
        // Vérifier que la réunion peut être finalisée
        if (!in_array($meeting->status, ['scheduled', 'prevalidated', 'validated'])) {
            return back()->with('error', 'Cette réunion ne peut pas être finalisée car elle est déjà terminée ou annulée.');
        }
        
        // Marquer les attendees sans statut explicite comme absents
        $meeting->attendees()
            ->whereNull('attendance_status')
            ->update(['attendance_status' => 'absent', 'is_present' => false]);
        
        // Marquer la réunion comme terminée
        $meeting->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => Auth::id()
        ]);
        
        return redirect()->route('meetings.show', $meeting->id)
            ->with('success', 'La liste de présence a été finalisée et la réunion a été marquée comme terminée.');
    }

    /**
     * Générer un PDF de la liste de présence.
     */
    public function exportPdf(Meeting $meeting)
    {
        $attendees = $meeting->attendees()
            ->with(['village', 'representative'])
            ->get();

        $pdf = PDF::loadView('pdf.attendance-list', [
            'meeting' => $meeting,
            'attendees' => $attendees,
            'generated_at' => now()->format('d/m/Y H:i')
        ]);

        return $pdf->download('liste-presence-'.$meeting->id.'.pdf');
    }
}
