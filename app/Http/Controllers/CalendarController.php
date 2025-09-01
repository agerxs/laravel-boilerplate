<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    public function index()
    {
        // Récupérer toutes les réunions avec leurs relations
        $allMeetings = Meeting::with(['localCommittee', 'subMeetings'])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        // Filtrer pour exclure les réunions parent et inclure seulement les sous-réunions
        $meetings = $allMeetings->flatMap(function ($meeting) {
            // Si c'est une réunion parent avec des sous-réunions, on ne l'affiche pas
            if ($meeting->isParentMeeting() && $meeting->subMeetings()->count() > 0) {
                return collect(); // Retourner une collection vide
            }
            
            // Si c'est une sous-réunion, on l'affiche
            if ($meeting->isSubMeeting()) {
                return collect([$meeting]);
            }
            
            // Si c'est une réunion normale sans sous-réunions, on l'affiche
            if ($meeting->isParentMeeting() && $meeting->subMeetings()->count() === 0) {
                return collect([$meeting]);
            }
            
            return collect(); // Par défaut, ne rien afficher
        })->map(function ($meeting) {
            return [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'scheduled_date' => $meeting->scheduled_date->format('Y-m-d H:i:s'),
                'location' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
                'status' => $meeting->status,
                'is_sub_meeting' => $meeting->isSubMeeting(),
                'parent_meeting_title' => $meeting->isSubMeeting() ? $meeting->parentMeeting?->title : null,
                'local_committee' => $meeting->localCommittee ? [
                    'id' => $meeting->localCommittee->id,
                    'name' => $meeting->localCommittee->name,
                ] : null,
            ];
        });

        return Inertia::render('Calendar/Index', [
            'meetings' => $meetings
        ]);
    }
} 