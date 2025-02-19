<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Notifications\AgendaUpdated;
use App\Services\MeetingExportService;
use App\Models\LocalCommittee;
use App\Services\MeetingInvitationService;
use Illuminate\Support\Facades\Mail;
use App\Mail\AgendaUpdatedMail;
use App\Notifications\MeetingCancelled;
use App\Mail\MeetingCancelledMail;
use App\Notifications\MeetingInvitation;
use App\Mail\GuestMeetingInvitation;

class MeetingController extends Controller
{
    public function __construct(
        protected MeetingInvitationService $invitationService
    ) {}

    public function index()
    {
        $meetings = Meeting::query()
            ->with(['localCommittee.locality'])
            ->orderBy('scheduled_date', 'desc')
            ->paginate(10)
            ->through(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'scheduled_date' => $meeting->scheduled_date,
                    'status' => $meeting->status,
                    'locality_name' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
                    'local_committee_id' => $meeting->local_committee_id
                ];
            });

        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings,
        ]);
    }

    public function create()
    {
        $committees = LocalCommittee::with('locality')
            ->get()
            ->map(function ($committee) {
                return [
                    'id' => $committee->id,
                    'name' => $committee->name,
                    'locality_name' => $committee->locality->name,
                ];
            });
        
        return Inertia::render('Meetings/Create', [
            'committees' => $committees
        ]);
    }

    public function show(Meeting $meeting)
    {
        $meeting->load(['localCommittee.locality']);
        
        return Inertia::render('Meetings/Show', [
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'scheduled_date' => $meeting->scheduled_date,
                'status' => $meeting->status,
                'locality_name' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
                'local_committee_id' => $meeting->local_committee_id,
                'local_committee' => [
                    'name' => $meeting->localCommittee?->name,
                    'locality' => [
                        'name' => $meeting->localCommittee?->locality?->name
                    ]
                ]
            ],
            'user' => auth()->user()
        ]);
    }

    public function edit(Meeting $meeting)
    {
        $meeting->load(['localCommittee.locality']);
        
        $committees = LocalCommittee::with('locality')
            ->get()
            ->map(function ($committee) {
                return [
                    'id' => $committee->id,
                    'name' => $committee->name,
                    'locality_name' => $committee->locality->name,
                ];
            });

        return Inertia::render('Meetings/Edit', [
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'scheduled_date' => $meeting->scheduled_date,
                'status' => $meeting->status,
                'local_committee_id' => $meeting->local_committee_id,
                'locality_name' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
            ],
            'committees' => $committees
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'local_committee_id' => 'required|exists:local_committees,id',
            'scheduled_date' => 'required|date',
            'status' => 'required|in:scheduled,completed,cancelled'
        ]);

        $meeting = Meeting::create($validated);

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Réunion créée avec succès');
    }

    public function update(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'local_committee_id' => 'required|exists:local_committees,id',
            'scheduled_date' => 'required|date',
            'status' => 'required|in:scheduled,completed,cancelled'
        ]);

        $meeting->update($validated);

        return redirect()->back()->with('success', 'Réunion mise à jour avec succès');
    }

    public function export(Meeting $meeting, MeetingExportService $exportService)
    {
        return $exportService->exportToPdf($meeting);
    }

    public function cancel(Meeting $meeting)
    {
        $meeting->update(['status' => 'cancelled']);
        
        return response()->json(['status' => 'success']);
    }

    public function notify(Meeting $meeting)
    {
        try {
            // Notifier les participants qui sont des utilisateurs
            $meeting->participants()
                ->whereNotNull('user_id')
                ->with('user')
                ->get()
                ->each(function ($participant) use ($meeting) {
                    if ($participant->user) {
                        $participant->user->notify(new MeetingInvitation($meeting));
                    }
                });

            // Envoyer des emails aux invités externes
            $meeting->participants()
                ->whereNotNull('guest_email')
                ->get()
                ->each(function ($participant) use ($meeting) {
                    Mail::to($participant->guest_email)
                        ->send(new GuestMeetingInvitation($meeting, $participant->guest_name));
                });

            return response()->json([
                'message' => 'Notifications envoyées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'envoi des notifications'
            ], 500);
        }
    }
} 