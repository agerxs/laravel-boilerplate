<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Meeting;
use App\Models\User;
use App\Models\LocalCommittee;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistiques générales
        $stats = [
            'total_meetings' => Meeting::count(),
            'upcoming_meetings' => Meeting::where('start_datetime', '>', now())->count(),
            'total_users' => User::count(),
            'total_committees' => LocalCommittee::count(),
        ];

        // Réunions à venir (prochains 7 jours)
        $upcomingMeetings = Meeting::with(['localCommittees'])
            ->where('start_datetime', '>', now())
            ->where('start_datetime', '<', now()->addDays(7))
            ->orderBy('start_datetime')
            ->take(5)
            ->get();

        // Données pour le graphique des réunions par mois
        $meetingsByMonth = Meeting::selectRaw('COUNT(*) as count, MONTH(start_datetime) as month')
            ->whereYear('start_datetime', date('Y'))
            ->groupBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        // Données pour le graphique des réunions par statut
        $meetingsByStatus = Meeting::selectRaw('COUNT(*) as count, status')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'upcomingMeetings' => $upcomingMeetings,
            'meetingsByMonth' => $meetingsByMonth,
            'meetingsByStatus' => $meetingsByStatus,
        ]);
    }
} 