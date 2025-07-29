<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingPaymentList;
use App\Models\MeetingPaymentItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\MeetingAttendee;
use App\Models\LocalCommittee;

class MeetingPaymentListController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = MeetingPaymentList::query()
            ->with(['meeting.localCommittee', 'submitter', 'paymentItems.attendee'])
            ->orderBy('created_at', 'desc');

        // Filtre par comité local
        if ($request->filled('local_committee_id')) {
            $query->whereHas('meeting.localCommittee', function ($q) use ($request) {
                $q->where('id', $request->local_committee_id);
            });
        }

        // Filtre par réunion
        if ($request->filled('meeting_id')) {
            $query->where('meeting_id', $request->meeting_id);
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par rôle (pour les paiements par type de participant)
        if ($request->filled('role')) {
            $query->whereHas('paymentItems', function ($q) use ($request) {
                $q->where('role', $request->role);
            });
        }

        $paymentLists = $query->paginate(10)
            ->through(function ($list) {
                $list->total_amount = $list->paymentItems->sum('amount');
                return $list;
            });

        return Inertia::render('MeetingPayments/Lists/Index', [
            'paymentLists' => $paymentLists,
            'meetings' => Meeting::orderBy('scheduled_date', 'desc')->get(),
            'localCommittees' => LocalCommittee::orderBy('name')->get(),
            'canValidate' => in_array('gestionnaire', $user->roles->pluck('name')->toArray()) || in_array('Gestionnaire', $user->roles->pluck('name')->toArray()),
            'filters' => $request->only(['local_committee_id', 'meeting_id', 'status', 'role'])
        ]);
    }

    public function create(Meeting $meeting)
    {
        // Vérifier si la réunion est terminée
        if ($meeting->status !== 'completed') {
            return redirect()->back()->with('error', 'La réunion doit être terminée pour créer une liste de paiement.');
        }

        // Vérifier si une liste existe déjà
        if ($meeting->paymentList()->exists()) {
            return redirect()->back()->with('error', 'Une liste de paiement existe déjà pour cette réunion.');
        }

        // Charger les participants présents
        $meeting->load(['attendees' => function($query) {
            $query->whereIn('attendance_status', ['present', 'replaced']);
        }]);

        return Inertia::render('MeetingPayments/Lists/Create', [
            'meeting' => $meeting
        ]);
    }

    public function store(Request $request, Meeting $meeting)
    {
        // Vérifier si la réunion est terminée
        if ($meeting->status !== 'completed') {
            return redirect()->back()->with('error', 'La réunion doit être terminée pour créer une liste de paiement.');
        }

        $validated = $request->validate([
            'attendees' => 'required|array',
            'attendees.*.id' => 'required|exists:meeting_attendees,id',
            'attendees.*.amount' => 'required|numeric|min:0',
            'attendees.*.role' => 'required|string',
        ]);

        $paymentList = MeetingPaymentList::create([
            'meeting_id' => $meeting->id,
            'submitted_by' => Auth::id(),
            'status' => 'submitted',
            'total_amount' => collect($validated['attendees'])->sum('amount'),
        ]);

        foreach ($validated['attendees'] as $item) {
            MeetingPaymentItem::create([
                'meeting_payment_list_id' => $paymentList->id,
                'attendee_id' => $item['id'],
                'amount' => $item['amount'],
                'role' => $item['role'],
                'payment_status' => 'pending'
            ]);
        }

        return redirect()->route('meeting-payments.lists.show', $paymentList->id)
            ->with('success', 'Liste de paiement créée avec succès.');
    }

    public function show(MeetingPaymentList $paymentList)
    {
        $paymentList->load([
            'meeting.localCommittee',
            'paymentItems.attendee',
            'submitter',
            'validator'
        ]);

        $user = Auth::user();
        $canValidate = in_array('sous-prefet', $user->roles->pluck('name')->toArray()) || in_array('Sous-prefet', $user->roles->pluck('name')->toArray());

        return Inertia::render('MeetingPayments/Lists/Show', [
            'paymentList' => $paymentList,
            'canValidate' => $canValidate,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'locality_id' => $user->locality_id
            ]
        ]);
    }

    public function submit(MeetingPaymentList $paymentList)
    {
        if ($paymentList->status !== 'draft') {
            return redirect()->back()->with('error', 'Cette liste ne peut pas être soumise.');
        }

        // Vérifier si l'utilisateur est un secrétaire
        if (!in_array('secretaire', Auth::user()->roles->pluck('name')->toArray()) && !in_array('Secretaire', Auth::user()->roles->pluck('name')->toArray())) {
            return redirect()->back()->with('error', 'Seul un secrétaire peut soumettre une liste de paiement.');
        }

        $paymentList->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // TODO: Envoyer une notification au président

        return redirect()->back()->with('success', 'Liste de paiement soumise pour validation.');
    }

    public function validates(MeetingPaymentList $paymentList)
    {
      info($paymentList);
        if ($paymentList->status !== 'submitted') {
            return redirect()->back()->with('error', 'Cette liste ne peut pas être validée.');
        }
        info('cafdd');

        if (!in_array('gestionnaire', Auth::user()->roles->pluck('name')->toArray()) && !in_array('Gestionnaire', Auth::user()->roles->pluck('name')->toArray())) {
            return redirect()->back()->with('error', 'Seul un gestionnaire peut valider cette liste.');
        }
        info('sksjkdj');
        $paymentList->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Liste de paiement validée.');
    }

    public function reject(Request $request, MeetingPaymentList $paymentList)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($paymentList->status !== 'submitted') {
            return redirect()->back()->with('error', 'Cette liste ne peut pas être rejetée.');
        }

        if (!in_array('sous-prefet', Auth::user()->roles->pluck('name')->toArray()) && !in_array('Sous-prefet', Auth::user()->roles->pluck('name')->toArray())) {
            return redirect()->back()->with('error', 'Vous n\'avez pas les droits pour rejeter cette liste.');
        }

        $paymentList->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // TODO: Envoyer une notification au secrétaire

        return redirect()->back()->with('success', 'Liste de paiement rejetée.');
    }

    public function validateItem(MeetingPaymentItem $item)
    {
        // Vérifier si l'utilisateur est un gestionnaire
        if (!in_array('gestionnaire', Auth::user()->roles->pluck('name')->toArray()) && !in_array('Gestionnaire', Auth::user()->roles->pluck('name')->toArray())) {
            return response()->json([
                'message' => 'Vous n\'avez pas les droits pour valider ce paiement.'
            ], 403);
        }

        // Vérifier si le paiement peut être validé
        if ($item->payment_status !== 'pending') {
            return response()->json([
                'message' => 'Ce paiement ne peut pas être validé.'
            ], 400);
        }

        // Mettre à jour le statut du paiement
        $item->update([
            'payment_status' => 'validated',
            'validated_at' => now(),
            'validated_by' => Auth::id(),
        ]);

        // Vérifier si tous les paiements de la liste sont validés
        $allValidated = $item->paymentList->paymentItems()
            ->where('payment_status', '!=', 'validated')
            ->count() === 0;

        if ($allValidated) {
            $item->paymentList->update([
                'status' => 'validated',
                'validated_at' => now(),
                'validated_by' => Auth::id(),
            ]);
        }

        return response()->json([
            'message' => 'Paiement validé avec succès.',
            'item' => $item
        ]);
    }

    public function invalidateItem(MeetingPaymentItem $item)
    {
        // Vérifier si l'utilisateur est un gestionnaire
        if (!in_array('gestionnaire', Auth::user()->roles->pluck('name')->toArray()) && !in_array('Gestionnaire', Auth::user()->roles->pluck('name')->toArray())) {
            return response()->json([
                'message' => 'Vous n\'avez pas les droits pour invalider ce paiement.'
            ], 403);
        }

        // Vérifier si le paiement peut être invalidé
        if ($item->payment_status !== 'validated') {
            return response()->json([
                'message' => 'Ce paiement ne peut pas être invalidé.'
            ], 400);
        }

        // Mettre à jour le statut du paiement
        $item->update([
            'payment_status' => 'pending',
            'validated_at' => null,
            'validated_by' => null,
        ]);

        // Mettre à jour le statut de la liste si nécessaire
        $paymentList = $item->paymentList;
        $pendingCount = $paymentList->paymentItems()
            ->where('payment_status', 'pending')
            ->count();

        if ($pendingCount > 0) {
            $paymentList->update([
                'status' => 'submitted',
                'validated_at' => null,
                'validated_by' => null,
            ]);
        }

        return response()->json([
            'message' => 'Paiement invalidé avec succès.',
            'item' => $item
        ]);
    }

    public function validateAll(Request $request)
    {
        // Vérifier si l'utilisateur est un gestionnaire
        if (!in_array('gestionnaire', Auth::user()->roles->pluck('name')->toArray()) && !in_array('Gestionnaire', Auth::user()->roles->pluck('name')->toArray())) {
            return response()->json([
                'message' => 'Vous n\'avez pas les droits pour valider les paiements.'
            ], 403);
        }

        $query = MeetingPaymentItem::query()
            ->where('payment_status', 'pending');

        // Filtrer par réunion si spécifié
        if ($request->has('meeting_id')) {
            $query->whereHas('paymentList', function($q) use ($request) {
                $q->where('meeting_id', $request->meeting_id);
            });
        }

        $items = $query->get();
        $validatedCount = 0;

        foreach ($items as $item) {
            $item->update([
                'payment_status' => 'validated',
                'validated_at' => now(),
                'validated_by' => Auth::id(),
            ]);
            $validatedCount++;

            // Vérifier si tous les paiements de la liste sont validés
            $allValidated = $item->paymentList->paymentItems()
                ->where('payment_status', '!=', 'validated')
                ->count() === 0;

            if ($allValidated) {
                $item->paymentList->update([
                    'status' => 'validated',
                    'validated_at' => now(),
                    'validated_by' => Auth::id(),
                ]);
            }
        }

        return response()->json([
            'message' => "{$validatedCount} paiements validés avec succès."
        ]);
    }

    public function update(Request $request, MeetingPaymentList $paymentList)
    {
        // Vérifier si la réunion est terminée
        if ($paymentList->meeting->status !== 'completed') {
            return redirect()->back()->with('error', 'La réunion doit être terminée pour mettre à jour la liste de paiement.');
        }

        $validated = $request->validate([
            'attendees' => 'required|array',
            'attendees.*.id' => 'required|exists:meeting_attendees,id',
            'attendees.*.amount' => 'required|numeric|min:0',
            'attendees.*.role' => 'required|string',
        ]);

        // Supprimer les anciens éléments
        $paymentList->paymentItems()->delete();

        // Créer les nouveaux éléments basés sur les participants présents
        $totalAmount = 0;
        foreach ($validated['attendees'] as $item) {
            $attendee = MeetingAttendee::find($item['id']);
            if (in_array($attendee->attendance_status, ['present', 'replaced'])) {
                $totalAmount += $item['amount'];
                MeetingPaymentItem::create([
                    'meeting_payment_list_id' => $paymentList->id,
                    'attendee_id' => $item['id'],
                    'amount' => $item['amount'],
                    'role' => $item['role'],
                    'payment_status' => 'pending'
                ]);
            }
        }

        // Mettre à jour le montant total
        $paymentList->update([
            'total_amount' => $totalAmount
        ]);

        return redirect()->route('meeting-payments.lists.show', $paymentList->id)
            ->with('success', 'Liste de paiement mise à jour avec succès.');
    }

    public function exportSingleMeeting(Request $request, Meeting $meeting)
    {
        $user = Auth::user();
        
        if (!in_array('gestionnaire', $user->roles->pluck('name')->toArray()) && !in_array('Gestionnaire', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $paymentList = MeetingPaymentList::with([
            'meeting.localCommittee',
            'paymentItems.attendee',
            'submitter',
            'validator'
        ])
        ->where('meeting_id', $meeting->id)
        ->first();

        if (!$paymentList) {
            return response()->json(['message' => 'Aucune liste de paiement trouvée pour cette réunion'], 404);
        }

        \Illuminate\Support\Facades\Log::info('Exporting payment list:', $paymentList->toArray());

        // Préparation des données pour l'export
        $exportData = [
            'Réunion' => $paymentList->meeting->title,
            'Date' => $paymentList->meeting->scheduled_date->format('d/m/Y'),
            'Comité Local' => $paymentList->meeting->localCommittee->name,
            'Montant Total' => $paymentList->total_amount,
            'Statut Liste' => $this->translateStatus($paymentList->status),
            'Participants' => $paymentList->paymentItems->map(function($item) use ($paymentList) {
                return [
                    'Nom' => $item->attendee->name,
                    'Rôle' => $this->translateRole($item->role),
                    'Montant' => $item->amount,
                    'Statut' => $this->translatePaymentStatus($item->payment_status)
                ];
            })
        ];

        return response()->json([
            'data' => $exportData,
            'total_amount' => $paymentList->total_amount,
            'meeting_title' => $paymentList->meeting->title
        ]);
    }

    public function exportPaymentLists(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array('gestionnaire', $user->roles->pluck('name')->toArray()) && !in_array('Gestionnaire', $user->roles->pluck('name')->toArray())) {
            //return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $query = MeetingPaymentList::query()
            ->with([
                'meeting.localCommittee',
                'paymentItems.attendee',
                'submitter',
                'validator'
            ]);
        
        // Filtrage par comité local
        if ($request->filled('local_committee_id')) {
            $query->whereHas('meeting.localCommittee', function($q) use ($request) {
                $q->where('id', $request->local_committee_id);
            });
        }
        // Filtrage par réunion
        if ($request->filled('meeting_id')) {
            $query->where('meeting_id', $request->meeting_id);
        }
        // Filtrage par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        

        $paymentLists = $query->get();

        // Préparation des données pour l'export
        $exportData = $paymentLists->map(function($list) {
            return [
                'Réunion' => $list->meeting->title,
                'Date' => $list->meeting->scheduled_date->format('d/m/Y'),
                'Comité Local' => $list->meeting->localCommittee->name,
                'Montant Total' => $list->total_amount,
                'Statut Liste' => $this->translateStatus($list->status),
                'Participants' => $list->paymentItems->map(function($item) {
                    return [
                        'Nom' => $item->attendee->name,
                        'Rôle' => $this->translateRole($item->role),
                        'Montant' => $item->amount,
                        'Statut' => $this->translatePaymentStatus($item->payment_status)
                    ];
                })
            ];
        });

        return response()->json([
            'data' => $exportData,
            'total_amount' => $paymentLists->sum('total_amount')
        ]);
    }

    private function calculateAmount($role, $meeting)
    {
        switch ($role) {
            case 'sous_prefet':
                return MeetingPaymentList::SUB_PREFET_AMOUNT;
            case 'secretaire':
                return MeetingPaymentList::SECRETARY_AMOUNT;
            case 'participant':
                return MeetingPaymentList::PARTICIPANT_AMOUNT;
            default:
                return MeetingPaymentList::PARTICIPANT_AMOUNT;
        }
    }

    private function translateRole($role)
    {
        $translations = [
            'sous_prefet' => 'Président',
            'secretaire' => 'Secrétaire',
            'participant' => 'Participant'
        ];
        return $translations[$role] ?? $role;
    }

    private function translateStatus($status)
    {
        $translations = [
            'draft' => 'Brouillon',
            'submitted' => 'Soumis',
            'validated' => 'Validé',
            'rejected' => 'Rejeté'
        ];
        return $translations[$status] ?? $status;
    }

    private function translatePaymentStatus($status)
    {
        $translations = [
            'pending' => 'En attente',
            'validated' => 'Validé',
            'paid' => 'Payé'
        ];
        return $translations[$status] ?? $status;
    }

    public function validatePaymentList(MeetingPaymentList $paymentList)
    {
        if ($paymentList->status !== 'submitted') {
            return redirect()->back()->with('error', 'Cette liste ne peut pas être validée.');
        }

        if (!in_array('gestionnaire', Auth::user()->roles->pluck('name')->toArray()) && !in_array('Gestionnaire', Auth::user()->roles->pluck('name')->toArray())) {
            return redirect()->back()->with('error', 'Seul un gestionnaire peut valider cette liste.');
        }

        $paymentList->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Liste de paiement validée.');
    }
} 