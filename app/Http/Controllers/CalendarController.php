<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    public function index()
    {
        $meetings = Meeting::with('localCommittee')
            ->orderBy('scheduled_date', 'asc')
            ->get()
            ->map(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'scheduled_date' => $meeting->scheduled_date->format('Y-m-d H:i:s'),
                    
                    'location' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
                    'status' => $meeting->status,
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