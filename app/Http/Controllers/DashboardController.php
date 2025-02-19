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
            'upcoming_meetings' => Meeting::where('scheduled_date', '>', now()->format('Y-m-d'))->count(),
            'total_users' => User::count(),
            'total_committees' => LocalCommittee::count(),
        ];

        // Réunions à venir (prochains 7 jours)
        $upcomingMeetings = Meeting::with(['localCommittee.locality'])
            ->where('scheduled_date', '>', now()->format('Y-m-d'))
            ->where('scheduled_date', '<', now()->addDays(7)->format('Y-m-d'))
            ->orderBy('scheduled_date')
            ->take(5)
            ->get();

        // Données pour le graphique des réunions par mois
        $meetingsByMonth = Meeting::selectRaw('COUNT(*) as count, MONTH(scheduled_date) as month')
            ->whereYear('scheduled_date', date('Y'))
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