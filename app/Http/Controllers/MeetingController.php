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

    public function index(Request $request)
    {
        $query = Meeting::query()
            ->with('localCommittee.locality')
            ->select('meetings.*');
        
        // Appliquer la recherche si elle existe
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }
        
        // Appliquer le tri
        $sortColumn = $request->input('sort', 'scheduled_date');
        $direction = $request->input('direction', 'desc');
        
        // Valider la colonne de tri pour éviter les injections SQL
        $allowedColumns = ['title', 'scheduled_date', 'status'];
        
        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $direction);
        } else if ($sortColumn === 'local_committee') {
            // Tri spécial pour le comité local (nécessite un join)
            $query->join('local_committees', 'meetings.local_committee_id', '=', 'local_committees.id')
                  ->join('localities', 'local_committees.locality_id', '=', 'localities.id')
                  ->orderBy('localities.name', $direction)
                  ->select('meetings.*'); // Important pour éviter les conflits de colonnes
        }
        
        $meetings = $query->paginate(10)
            ->withQueryString()
            ->through(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'scheduled_date' => $meeting->scheduled_date,
                    'status' => $meeting->status,
                    'locality_name' => $meeting->localCommittee->locality->name ?? 'Non défini',
                ];
            });
        
        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings,
            'filters' => [
                'search' => $request->input('search', ''),
                'sort' => $sortColumn,
                'direction' => $direction
            ]
        ]);
    }

    public function create()
    {
        $localCommittees = LocalCommittee::all();
        return Inertia::render('Meetings/Create', [
            'localCommittees' => $localCommittees,
        ]);
    }

    public function show(Meeting $meeting)
    {
       
        $meeting->load(['localCommittee']);
        $committee = LocalCommittee::with(['locality.children.representatives'])->findOrFail($meeting->local_committee_id);
       
        
        return Inertia::render('Meetings/Show', [
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'scheduled_date' => $meeting->scheduled_date,
                'status' => $meeting->status,
                'locality_name' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
                'local_committee_id' => $meeting->local_committee_id,
                'local_committee' => $committee
            ],
            'user' => auth()->user()
        ]);
    }

    public function edit(Meeting $meeting)
    {
        $meeting->load(['localCommittee.locality']);
        
        $localCommittees = LocalCommittee::all();

        return Inertia::render('Meetings/Edit', [
            'meeting' => $meeting,
            'localCommittees' => $localCommittees,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'localCommittee' => 'required|exists:local_committees,id',
        ]);

        $validated['local_committee_id'] = $validated['localCommittee'];
        unset($validated['localCommittee']);

        Meeting::create($validated);

        return redirect()->route('meetings.index')->with('success', 'Réunion planifiée avec succès');
    }

    public function update(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'localCommittee' => 'required|exists:local_committees,id',
        ]);

        $validated['local_committee_id'] = $validated['localCommittee'];
        unset($validated['localCommittee']);

        $meeting->update($validated);

        return redirect()->route('meetings.index')->with('success', 'Réunion mise à jour avec succès');
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

    public function reschedule(Request $request, $meetingId)
    {
        $request->validate([
            'date' => 'required|date',
            'reason' => 'required|string|max:255',
        ]);

        $meeting = Meeting::findOrFail($meetingId);
        $meeting->scheduled_date = $request->input('date');
        $meeting->reschedule_reason = $request->input('reason');
        $meeting->save();

        return response()->json(['message' => 'La réunion a été reportée avec succès.']);
    }

    public function showRescheduleForm($meetingId)
    {
        $meeting = Meeting::findOrFail($meetingId);

        return Inertia::render('Meetings/Reschedule', [
            'meetingId' => $meeting->id,
            'meeting' => [
                'title' => $meeting->title,
                'scheduled_date' => $meeting->scheduled_date,
                'locality_name' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
            ],
        ]);
    }
} 