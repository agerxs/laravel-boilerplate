<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Notifications\AgendaUpdated;
use App\Services\MeetingExportService;

class MeetingController extends Controller
{
    public function index()
    {
        $meetings = Meeting::query()
            ->orderBy('start_datetime', 'desc')
            ->paginate(10);

        \Log::info('Meetings query result:', ['meetings' => $meetings->toArray()]);

        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings
        ]);
    }

    public function create()
    {
        $users = User::select('id', 'name', 'email')->get();
        
        return Inertia::render('Meetings/Create', [
            'users' => $users
        ]);
    }

    public function show(Meeting $meeting)
    {
        $meeting->load([
            'participants', 
            'agenda.presenter', 
            'attachments.uploader',
            'minutes'
        ]);
        
        return Inertia::render('Meetings/Show', [
            'meeting' => $meeting,
            'user' => auth()->user()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'location' => 'nullable|string',
            'participants' => 'required|array',
            'participants.*' => 'exists:users,id'
        ]);

        $meeting = Meeting::create([
            ...$validated,
            'organizer_id' => auth()->id(),
            'status' => 'planned'
        ]);

        $meeting->participants()->attach($validated['participants'], ['status' => 'pending']);

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

            // Envoyer les notifications si l'agenda a été modifié
            if ($agendaWasUpdated) {
                $meeting->participants->each(function ($participant) use ($meeting) {
                    $participant->notify(new AgendaUpdated($meeting));
                });
            }
        });

        return redirect()->back()->with('success', 'Réunion mise à jour avec succès');
    }

    public function export(Meeting $meeting, MeetingExportService $exportService)
    {
        return $exportService->exportToPdf($meeting);
    }
} 