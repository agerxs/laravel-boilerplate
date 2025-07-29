<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\PaymentRate;
use App\Models\MeetingPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MeetingPaymentController extends Controller
{
    /**
     * Affiche la liste des paiements pour les réunions
     */
    public function index(Request $request)
    {
        $meetings = Meeting::with(['localCommittee', 'attendees', 'payments'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('scheduled_date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('scheduled_date', '<=', $dateTo);
            })
            ->orderBy('scheduled_date', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MeetingPayments/Index', [
            'meetings' => $meetings,
            'filters' => $request->only(['search', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Affiche le détail des paiements pour une réunion spécifique
     */
    public function show(Meeting $meeting)
    {
        $meeting->load(['localCommittee', 'attendees', 'payments']);
        
        // Récupérer les officiels associés à cette réunion
        $officials = [];
        
        
        $sousPrefet = User::whereHas('roles', function ($query) {
            $query->where('name', 'sous_prefet');
        })->first();
        
        // Secrétaire (un seul par réunion)
        $secretaire = User::whereHas('roles', function ($query) {
            $query->where('name', 'secretaire');
        })->first();
        
        // Représentants (plusieurs par réunion)
        $representants = $meeting->attendees()
            ->whereHas('user.roles', function ($query) {
                $query->where('name', 'representant');
            })
            ->with('user')
            ->get();
        
        // Récupérer les taux de paiement pour chaque rôle
        $paymentRates = [
            'sous_prefet' => PaymentRate::getActiveRateForRole('sous_prefet'),
            'secretaire' => PaymentRate::getActiveRateForRole('secretaire'),
            'representant' => PaymentRate::getActiveRateForRole('representant'),
        ];
        
        // Préparer les données pour la vue
        $paymentData = [];
        
        
        
        // Ajouter le président
        if ($sousPrefet && $paymentRates['sous_prefet']) {
            $paymentData[] = $this->preparePaymentData($meeting, $sousPrefet, 'sous_prefet', $paymentRates['sous_prefet']);
        }
        
        // Ajouter le secrétaire
        if ($secretaire && $paymentRates['secretaire']) {
            $paymentData[] = $this->preparePaymentData($meeting, $secretaire, 'secretaire', $paymentRates['secretaire']);
        }
        
        // Ajouter les représentants
        if ($paymentRates['representant']) {
            foreach ($representants as $attendee) {
                $paymentData[] = $this->preparePaymentData($meeting, $attendee->user, 'representant', $paymentRates['representant']);
            }
        }

        return Inertia::render('MeetingPayments/Show', [
            'meeting' => $meeting,
            'paymentData' => $paymentData,
        ]);
    }

    /**
     * Prépare les données de paiement pour un utilisateur
     */
    private function preparePaymentData(Meeting $meeting, User $user, string $role, PaymentRate $rate)
    {
        // Vérifier si un paiement existe déjà pour cet utilisateur et cette réunion
        $existingPayment = $meeting->payments()
            ->where('user_id', $user->id)
            ->first();
        
        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role' => $role,
            'rate' => $rate->meeting_rate,
            'amount' => $existingPayment ? $existingPayment->amount : $rate->meeting_rate,
            'is_paid' => $existingPayment ? $existingPayment->is_paid : false,
            'payment_date' => $existingPayment ? $existingPayment->payment_date : null,
            'payment_method' => $existingPayment ? $existingPayment->payment_method : null,
            'reference_number' => $existingPayment ? $existingPayment->reference_number : null,
            'notes' => $existingPayment ? $existingPayment->notes : null,
        ];
    }

    /**
     * Traite les paiements pour une réunion
     */
    public function processPayments(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'payments' => 'required|array',
            'payments.*.user_id' => 'required|exists:users,id',
            'payments.*.role' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.is_paid' => 'required|boolean',
            'payments.*.payment_date' => 'nullable|date',
            'payments.*.payment_method' => 'nullable|string',
            'payments.*.reference_number' => 'nullable|string',
            'payments.*.notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Traiter chaque paiement
        foreach ($validated['payments'] as $paymentData) {
            MeetingPayment::updateOrCreate(
                [
                    'meeting_id' => $meeting->id,
                    'user_id' => $paymentData['user_id'],
                ],
                [
                    'role' => $paymentData['role'],
                    'amount' => $paymentData['amount'],
                    'is_paid' => $paymentData['is_paid'],
                    'payment_date' => $paymentData['is_paid'] ? $paymentData['payment_date'] : null,
                    'payment_method' => $paymentData['is_paid'] ? $paymentData['payment_method'] : null,
                    'reference_number' => $paymentData['is_paid'] ? ($paymentData['reference_number'] ?? null) : null,
                    'notes' => $paymentData['notes'] ?? null,
                ]
            );
        }

        // Enregistrer les notes générales si nécessaire
        if (isset($validated['notes'])) {
            $meeting->payment_notes = $validated['notes'];
            $meeting->save();
        }
        
        return redirect()->route('meeting-payments.show', $meeting)
            ->with('success', 'Les paiements ont été traités avec succès.');
    }
} 