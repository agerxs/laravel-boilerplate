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
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Meeting::query();
        $committeeQuery = LocalCommittee::query();

        // Filtrer les données en fonction du rôle
        if (in_array('prefet', $user->roles->pluck('name')->toArray()) || in_array('Prefet', $user->roles->pluck('name')->toArray())) {
            // Pour les préfets, montrer les données de leur département et sous-préfectures
            $query->whereHas('localCommittee.locality', function ($q) use ($user) {
                $q->where('id', $user->locality_id)
                  ->orWhere('parent_id', $user->locality_id);
            });
            $committeeQuery->whereHas('locality', function ($q) use ($user) {
                $q->where('id', $user->locality_id)
                  ->orWhere('parent_id', $user->locality_id);
            });
        } elseif (in_array('sous-prefet', $user->roles->pluck('name')->toArray()) || 
                   in_array('Sous-prefet', $user->roles->pluck('name')->toArray()) ||
                   in_array('secretaire', $user->roles->pluck('name')->toArray()) ||
                   in_array('Secrétaire', $user->roles->pluck('name')->toArray())) {
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

        if (in_array('gestionnaire', $user->roles->pluck('name')->toArray()) || in_array('Gestionnaire', $user->roles->pluck('name')->toArray())) {
            // Statistiques des paiements
            $stats['total_payments'] = MeetingPaymentList::where('status', 'validated')->sum('total_amount');
            $stats['pending_payments'] = MeetingPaymentList::where('status', 'submitted')->count();
            $stats['pending_payments_amount'] = MeetingPaymentList::where('status', 'submitted')->sum('total_amount');
            $stats['draft_payments'] = MeetingPaymentList::where('status', 'draft')->count();
            $stats['validated_payments'] = MeetingPaymentList::where('status', 'validated')->count();
            
            // Statistiques des paiements des sous-préfets
            $stats['sub_prefet_payments'] = MeetingPaymentList::whereHas('paymentItems', function($query) {
                $query->where('role', 'sous-prefet')
                      ->where('payment_status', 'validated');
            })->sum('total_amount');
            
            $stats['sub_prefet_pending'] = MeetingPaymentList::whereHas('paymentItems', function($query) {
                $query->where('role', 'sous-prefet')
                      ->where('payment_status', 'pending');
            })->count();
            
            // Statistiques des paiements des secrétaires
            $stats['secretary_payments'] = MeetingPaymentList::whereHas('paymentItems', function($query) {
                $query->where('role', 'secretaire')
                      ->where('payment_status', 'validated');
            })->sum('total_amount');
            
            $stats['secretary_pending'] = MeetingPaymentList::whereHas('paymentItems', function($query) {
                $query->where('role', 'secretaire')
                      ->where('payment_status', 'pending');
            })->count();

            // Statistiques par rôle
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

            // Dernières listes de paiement soumises (pour l'affichage dans le tableau)
            $stats['recent_payment_lists'] = MeetingPaymentList::with(['meeting.localCommittee', 'submitter'])
                ->whereIn('status', ['submitted', 'validated'])
                ->orderBy('submitted_at', 'desc')
                ->take(10)
                ->get();
        }

        // Statistiques utilisateurs par rôle
        $usersByRole = Role::whereNotIn('name', ['admin', 'prefet', 'Admin', 'Prefet'])->get()->map(function ($role) {
            return [
                'name' => $role->name,
                'count' => User::role($role->name)->count(),
            ];
        });

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
            ],
            'usersByRole' => $usersByRole,
        ]);
    }
} 