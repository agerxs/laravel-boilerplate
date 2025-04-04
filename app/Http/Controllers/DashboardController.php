<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Meeting;
use App\Models\User;
use App\Models\LocalCommittee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\MeetingPaymentList;

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

        if ($user->hasRole(['gestionnaire', 'Gestionnaire'])) {
            // Statistiques des paiements
            $stats['total_payments'] = MeetingPaymentList::where('status', 'validated')->sum('total_amount');
            $stats['pending_payments'] = MeetingPaymentList::where('status', 'submitted')->count();
            $stats['draft_payments'] = MeetingPaymentList::where('status', 'draft')->count();
            $stats['validated_payments'] = MeetingPaymentList::where('status', 'validated')->count();
            
            // Statistiques par rôle
            $stats['sub_prefet_payments'] = MeetingPaymentList::whereHas('paymentItems', function($query) {
                $query->where('role', 'sous_prefet')
                      ->where('payment_status', 'validated');
            })->sum('total_amount');
            
            $stats['secretary_payments'] = MeetingPaymentList::whereHas('paymentItems', function($query) {
                $query->where('role', 'secretaire')
                      ->where('payment_status', 'validated');
            })->sum('total_amount');
            
            $stats['participant_payments'] = MeetingPaymentList::whereHas('paymentItems', function($query) {
                $query->where('role', 'participant')
                      ->where('payment_status', 'validated');
            })->sum('total_amount');

            // Réunions avec paiements en attente
            $stats['meetings_with_pending_payments'] = Meeting::whereHas('paymentList', function($query) {
                $query->where('status', 'submitted');
            })->count();

            // Comités locaux avec paiements en attente
            $stats['committees_with_pending_payments'] = LocalCommittee::whereHas('meetings.paymentList', function($query) {
                $query->where('status', 'submitted');
            })->count();

            // Dernières listes de paiement en attente
            $stats['pending_payment_lists'] = MeetingPaymentList::with(['meeting.localCommittee', 'submitter'])
                ->where('status', 'submitted')
                ->orderBy('submitted_at', 'desc')
                ->take(5)
                ->get();
            
            // Dernières listes de paiement en brouillon
            $stats['draft_payment_lists'] = MeetingPaymentList::with(['meeting.localCommittee', 'submitter'])
                ->where('status', 'draft')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'upcomingMeetings' => $upcomingMeetings,
            'meetingsByMonth' => $meetingsByMonth,
            'meetingsByStatus' => $meetingsByStatus,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'locality_id' => $user->locality_id
            ]
        ]);
    }
} 