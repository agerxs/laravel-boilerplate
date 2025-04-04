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

class MeetingPaymentListController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = MeetingPaymentList::query()
            ->with([
                'meeting.localCommittee',
                'submitter',
                'validator',
                'paymentItems.attendee'
            ]);

        // Filtrer selon le rôle
        if ($user->hasRole(['secretaire', 'Secrétaire', 'gestionnaire', 'Gestionnaire'])) {
            $query->whereHas('meeting.localCommittee', function($q) use ($user) {
                $q->where('locality_id', $user->locality_id);
            });
        } elseif ($user->hasRole(['sous-prefet', 'Sous-prefet'])) {
            $query->whereHas('meeting.localCommittee', function($q) use ($user) {
                $q->where('locality_id', $user->locality_id);
            });
        }

        $paymentLists = $query->latest()->paginate(10);

        // Récupérer uniquement les réunions qui ont des listes de paiement
        $meetings = Meeting::whereHas('paymentList')
            ->whereHas('localCommittee', function($q) use ($user) {
                $q->where('locality_id', $user->locality_id);
            })
            ->get();

        return Inertia::render('MeetingPayments/Lists/Index', [
            'paymentLists' => $paymentLists,
            'meetings' => $meetings,
            'canValidate' => $user->hasRole(['gestionnaire', 'Gestionnaire'])
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
            'status' => 'draft',
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
        $canValidate = $user->hasRole(['sous-prefet', 'Sous-prefet']);

        return Inertia::render('MeetingPayments/Lists/Show', [
            'paymentList' => $paymentList,
            'canValidate' => $canValidate
        ]);
    }

    public function submit(MeetingPaymentList $paymentList)
    {
        if ($paymentList->status !== 'draft') {
            return redirect()->back()->with('error', 'Cette liste ne peut pas être soumise.');
        }

        $paymentList->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // TODO: Envoyer une notification au sous-préfet

        return redirect()->back()->with('success', 'Liste de paiement soumise pour validation.');
    }

    public function validates(MeetingPaymentList $list)
    {
        if ($list->status !== 'submitted') {
            return redirect()->back()->with('error', 'Cette liste ne peut pas être validée.');
        }

        if (!Auth::user()->hasRole(['sous-prefet', 'Sous-prefet'])) {
            return redirect()->back()->with('error', 'Vous n\'avez pas les droits pour valider cette liste.');
        }

        $list->update([
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

        if (!Auth::user()->hasRole(['sous-prefet', 'Sous-prefet'])) {
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
        if (!Auth::user()->hasRole(['gestionnaire', 'Gestionnaire'])) {
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
        $allValidated = $item->paymentList->items()
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

    public function validateAll(Request $request)
    {
        // Vérifier si l'utilisateur est un gestionnaire
        if (!Auth::user()->hasRole(['gestionnaire', 'Gestionnaire'])) {
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
            $allValidated = $item->paymentList->items()
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
} 