<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingPayment;
use App\Models\PaymentRate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class ExecutivePaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('--- ExecutivePaymentController: Début de la récupération des paiements ---');

        $query = MeetingPayment::with(['user.roles', 'meeting.localCommittee']);

        // Filtres
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Log de la requête SQL de base
        \Illuminate\Support\Facades\Log::info('SQL Query:', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        // Enrichir les données avec les réunions déclencheuses
        $payments->getCollection()->transform(function ($payment) {
            if ($payment->triggering_meetings && is_array($payment->triggering_meetings)) {
                $triggeringMeetings = Meeting::whereIn('id', $payment->triggering_meetings)
                    ->select('id', 'title', 'scheduled_date')
                    ->get()
                    ->map(function ($meeting) {
                        return [
                            'id' => $meeting->id,
                            'title' => $meeting->title,
                            'date' => $meeting->scheduled_date->format('d/m/Y')
                        ];
                    });
                $payment->triggering_meetings_data = $triggeringMeetings;
            }
            return $payment;
        });

        // Log des résultats
        \Illuminate\Support\Facades\Log::info('Paiements trouvés:', [
            'total_count' => $payments->total(),
            'items_on_current_page' => $payments->count(),
            'data' => $payments->toArray()
        ]);
        
        \Illuminate\Support\Facades\Log::info('--- ExecutivePaymentController: Fin de la récupération ---');

        return Inertia::render('ExecutivePayments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['role', 'payment_status', 'date_from', 'date_to'])
        ]);
    }

    /**
     * Met à jour le statut d'un paiement de cadre
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,validated,paid,cancelled',
        ]);

        $payment = MeetingPayment::findOrFail($id);
        $oldStatus = $payment->payment_status;
        $payment->payment_status = $request->payment_status;

        // Historique des dates et utilisateurs
        if ($oldStatus !== $request->payment_status) {
            $user = $request->user();
            if ($request->payment_status === 'validated' && $user) {
                $payment->validated_at = now();
                $payment->validated_by = $user->id;
            }
            if ($request->payment_status === 'paid' && $user) {
                $payment->paid_at = now();
                $payment->paid_by = $user->id;
            }
        }
        $payment->save();

        // Retourner une réponse Inertia avec un message flash
        return back()->with('success', 'Statut du paiement mis à jour avec succès.');
    }

    /**
     * Exporte tous les paiements des cadres
     */
    public function exportAll(Request $request)
    {
        $query = MeetingPayment::with(['user', 'meeting.localCommittee']);

        // Filtres
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        $exportData = $payments->map(function($payment) {
            $triggeringMeetings = json_decode($payment->triggering_meetings, true);
            $meetingTitles = [];
            
            if ($triggeringMeetings) {
                foreach ($triggeringMeetings as $meetingId) {
                    $meeting = Meeting::find($meetingId);
                    if ($meeting) {
                        $meetingTitles[] = $meeting->title . ' (' . $meeting->scheduled_date->format('d/m/Y') . ')';
                    }
                }
            }

            return [
                'ID' => $payment->id,
                'Nom du cadre' => $payment->user->name,
                'Rôle' => $this->translateRole($payment->role),
                'Montant' => number_format($payment->amount, 0, ',', ' ') . ' FCFA',
                'Statut' => $this->translatePaymentStatus($payment->payment_status),
                'Réunions déclencheuses' => implode(' et ', $meetingTitles),
                'Comité local' => $payment->meeting->localCommittee->name ?? 'N/A',
                'Date de création' => $payment->created_at->format('d/m/Y H:i'),
                'Date de validation' => $payment->validated_at ? $payment->validated_at->format('d/m/Y H:i') : 'N/A',
                'Date de paiement' => $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : 'N/A',
            ];
        });

        return response()->json([
            'data' => $exportData,
            'total_count' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'filename' => 'paiements_cadres_' . now()->format('Y-m-d_H-i-s')
        ]);
    }

    /**
     * Exporte les paiements non effectués (pending et validated)
     */
    public function exportPending(Request $request)
    {
        $query = MeetingPayment::with(['user', 'meeting.localCommittee'])
            ->whereIn('payment_status', ['pending', 'validated']);

        // Filtres
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        $exportData = $payments->map(function($payment) {
            $triggeringMeetings = json_decode($payment->triggering_meetings, true);
            $meetingTitles = [];
            
            if ($triggeringMeetings) {
                foreach ($triggeringMeetings as $meetingId) {
                    $meeting = Meeting::find($meetingId);
                    if ($meeting) {
                        $meetingTitles[] = $meeting->title . ' (' . $meeting->scheduled_date->format('d/m/Y') . ')';
                    }
                }
            }

            return [
                'ID' => $payment->id,
                'Nom du cadre' => $payment->user->name,
                'Rôle' => $this->translateRole($payment->role),
                'Montant' => number_format($payment->amount, 0, ',', ' ') . ' FCFA',
                'Statut' => $this->translatePaymentStatus($payment->payment_status),
                'Réunions déclencheuses' => implode(' et ', $meetingTitles),
                'Comité local' => $payment->meeting->localCommittee->name ?? 'N/A',
                'Date de création' => $payment->created_at->format('d/m/Y H:i'),
                'Date de validation' => $payment->validated_at ? $payment->validated_at->format('d/m/Y H:i') : 'N/A',
            ];
        });

        return response()->json([
            'data' => $exportData,
            'total_count' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'filename' => 'paiements_cadres_non_effectues_' . now()->format('Y-m-d_H-i-s')
        ]);
    }

    /**
     * Traduit les rôles
     */
    private function translateRole($role)
    {
        $translations = [
            'secretaire' => 'Secrétaire',
            'sous-prefet' => 'Sous-préfet'
        ];
        return $translations[$role] ?? $role;
    }

    /**
     * Traduit les statuts de paiement
     */
    private function translatePaymentStatus($status)
    {
        $translations = [
            'pending' => 'En attente',
            'validated' => 'Validé',
            'paid' => 'Payé',
            'cancelled' => 'Annulé'
        ];
        return $translations[$status] ?? $status;
    }
} 