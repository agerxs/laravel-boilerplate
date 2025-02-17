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
            ->with('minutes')
            ->orderBy('start_datetime', 'desc')
            ->paginate(10);

        \Log::info('Meetings query result:', ['meetings' => $meetings->toArray()]);

        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings
        ]);
    }

    public function create()
    {
        $committees = LocalCommittee::with(['members.user' => function ($query) {
            $query->select('id', 'name', 'email');
        }])->get();
        
        return Inertia::render('Meetings/Create', [
            'committees' => $committees
        ]);
    }

    public function show(Meeting $meeting)
    {
        $meeting->load([
            'participants.user',
            'agenda.presenter',
            'minutes',
            'attachments.uploader',
            'enrollmentRequests'
        ]);
        
        return Inertia::render('Meetings/Show', [
            'meeting' => $meeting,
            'user' => auth()->user()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'location' => 'required|string',
            'local_committee_id' => 'required|exists:local_committees,id',
            'guests' => 'array',
            'guests.*.name' => 'required|string',
            'guests.*.email' => 'required|email',
            'agenda' => 'array',
            'agenda.*.title' => 'required|string',
            'agenda.*.description' => 'nullable|string',
            'agenda.*.duration_minutes' => 'required|integer',
            'agenda.*.presenter_id' => 'nullable|exists:users,id'
        ]);

        $meeting = DB::transaction(function () use ($validated) {
            $meeting = Meeting::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'start_datetime' => $validated['start_datetime'],
                'end_datetime' => $validated['end_datetime'],
                'location' => $validated['location'],
                'organizer_id' => auth()->id(),
                'status' => 'planned'
            ]);

            // Associer le comité local
            $meeting->localCommittees()->attach($validated['local_committee_id']);

            // Ajouter les membres du comité comme participants
            $committee = LocalCommittee::with('members.user')->find($validated['local_committee_id']);
            foreach ($committee->members as $member) {
                $meeting->participants()->create([
                    'user_id' => $member->user_id,
                    'status' => 'pending'
                ]);
            }

            // Créer les participants (invités externes)
            if (!empty($validated['guests'])) {
                foreach ($validated['guests'] as $guest) {
                    $meeting->participants()->create([
                        'guest_name' => $guest['name'],
                        'guest_email' => $guest['email']
                    ]);
                }
            }

            // Créer les points de l'ordre du jour
            if (!empty($validated['agenda'])) {
                foreach ($validated['agenda'] as $index => $item) {
                    $meeting->agenda()->create([
                        'title' => $item['title'],
                        'description' => $item['description'],
                        'duration_minutes' => $item['duration_minutes'],
                        'presenter_id' => $item['presenter_id'],
                        'order' => $index
                    ]);
                }
            }

            return $meeting;
        });

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Réunion créée avec succès');
    }

    public function update(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'agenda' => 'array',
            'agenda.*.title' => 'required|string',
            'agenda.*.description' => 'nullable|string',
            'agenda.*.duration_minutes' => 'required|integer',
            'agenda.*.presenter_id' => 'nullable|exists:users,id',
            'minutes' => 'nullable|array',
            'minutes.content' => 'nullable|string',
            'minutes.status' => 'nullable|string|in:draft,published',
            'attachments' => 'array'
        ]);

        DB::transaction(function () use ($meeting, $validated) {
            $agendaWasUpdated = false;

            if (isset($validated['agenda'])) {
                $oldAgenda = $meeting->agenda->pluck('id')->toArray();
                
                // D'abord, supprimer les anciens points qui ne sont plus présents
                $newIds = collect($validated['agenda'])
                    ->pluck('id')
                    ->filter()
                    ->toArray();
                
                $meeting->agenda()
                    ->whereNotIn('id', $newIds)
                    ->delete();

                // Ensuite, créer ou mettre à jour les points
                foreach ($validated['agenda'] as $index => $item) {
                    if (isset($item['id']) && $item['id'] > 0) {
                        $meeting->agenda()->where('id', $item['id'])->update([
                            'title' => $item['title'],
                            'description' => $item['description'],
                            'duration_minutes' => $item['duration_minutes'],
                            'presenter_id' => $item['presenter_id'],
                            'order' => $index
                        ]);
                    } else {
                        $meeting->agenda()->create([
                            'title' => $item['title'],
                            'description' => $item['description'],
                            'duration_minutes' => $item['duration_minutes'],
                            'presenter_id' => $item['presenter_id'],
                            'order' => $index
                        ]);
                    }
                }

                // Vérifier si l'agenda a été modifié
                $agendaWasUpdated = $oldAgenda != collect($validated['agenda'])->pluck('id')->toArray();
            }

            // Mise à jour des minutes seulement si elles sont fournies
            if (isset($validated['minutes']) && isset($validated['minutes']['content'])) {
                if ($meeting->minutes) {
                    $meeting->minutes->update($validated['minutes']);
                } else {
                    $meeting->minutes()->create($validated['minutes']);
                }
            }

            // Mettre à jour le statut si le compte-rendu est renseigné
            if (isset($validated['minutes']) && 
                isset($validated['minutes']['content']) && 
                $validated['minutes']['content'] && 
                $meeting->status === 'planned') {
                $meeting->update(['status' => 'completed']);
            }

            // Envoyer les notifications si l'agenda a été modifié
            if ($agendaWasUpdated) {
                // Pour les participants qui sont des utilisateurs
                $meeting->participants()
                    ->whereNotNull('user_id')
                    ->with('user')
                    ->get()
                    ->each(function ($participant) use ($meeting) {
                        if ($participant->user) {
                            $participant->user->notify(new AgendaUpdated($meeting));
                        }
                    });

                // Pour les invités externes
                $meeting->participants()
                    ->whereNotNull('guest_email')
                    ->get()
                    ->each(function ($participant) use ($meeting) {
                        // Envoyer un email directement à l'invité
                        Mail::to($participant->guest_email)
                            ->send(new AgendaUpdatedMail($meeting));
                    });
            }
        });

        return redirect()->back()->with('success', 'Réunion mise à jour avec succès');
    }

    public function export(Meeting $meeting, MeetingExportService $exportService)
    {
        return $exportService->exportToPdf($meeting);
    }

    public function cancel(Meeting $meeting)
    {
        $meeting->update(['status' => 'cancelled']);
        
        // Optionnel : Notifier les participants de l'annulation
        $meeting->participants()
            ->whereNotNull('user_id')
            ->with('user')
            ->get()
            ->each(function ($participant) use ($meeting) {
                if ($participant->user) {
                    $participant->user->notify(new MeetingCancelled($meeting));
                }
            });

        $meeting->participants()
            ->whereNotNull('guest_email')
            ->get()
            ->each(function ($participant) use ($meeting) {
                Mail::to($participant->guest_email)
                    ->send(new MeetingCancelledMail($meeting));
            });

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