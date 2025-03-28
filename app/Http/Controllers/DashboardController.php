<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Meeting;
use App\Models\User;
use App\Models\LocalCommittee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Meeting::query();
        $committeeQuery = LocalCommittee::query();

        // Filtrer les données en fonction du rôle
        if ($user->hasRole(['prefet', 'Prefet'])) {
            // Pour les préfets, montrer les données de leur département et sous-préfectures
            $query->whereHas('localCommittee.locality', function ($q) use ($user) {
                $q->where('id', $user->locality_id)
                  ->orWhere('parent_id', $user->locality_id);
            });
            $committeeQuery->whereHas('locality', function ($q) use ($user) {
                $q->where('id', $user->locality_id)
                  ->orWhere('parent_id', $user->locality_id);
            });
        } elseif ($user->hasRole(['sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire'])) {
            // Pour les sous-préfets et secrétaires, montrer uniquement les données de leur localité
            $query->whereHas('localCommittee.locality', function ($q) use ($user) {
                $q->where('id', $user->locality_id);
            });
            $committeeQuery->where('locality_id', $user->locality_id);
        }

        // Statistiques générales
        $stats = [
            'total_meetings' => $query->count(),
            'upcoming_meetings' => $query->where('scheduled_date', '>', now()->format('Y-m-d'))->count(),
            'total_users' => User::count(),
            'total_committees' => $committeeQuery->count(),
        ];

        // Réunions à venir (prochains 7 jours)
        $upcomingMeetings = $query->with(['localCommittee.locality'])
            ->where('scheduled_date', '>', now()->format('Y-m-d'))
            ->where('scheduled_date', '<', now()->addDays(30)->format('Y-m-d'))
            ->orderBy('scheduled_date')
            ->take(5)
            ->get();

        // Données pour le graphique des réunions par mois
        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays(7)->format('Y-m-d');
        
        $meetingsByMonth = Meeting::query()
            ->whereHas('localCommittee', function($q) use ($user) {
                $q->where('locality_id', $user->locality_id);
            })
            ->selectRaw('COUNT(*) as count, MONTH(scheduled_date) as month')
            ->whereYear('scheduled_date', date('Y'))
            ->groupBy(DB::raw('MONTH(scheduled_date)'))
            ->orderBy('month', 'asc')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        // Données pour le graphique des réunions par statut
        $meetingsByStatus = Meeting::query()
            ->whereHas('localCommittee', function($q) use ($user) {
                $q->where('locality_id', $user->locality_id);
            })
            ->selectRaw('COUNT(*) as count, status')
            ->whereYear('scheduled_date', date('Y'))
            ->groupBy('status')
            ->orderBy('status', 'asc')
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