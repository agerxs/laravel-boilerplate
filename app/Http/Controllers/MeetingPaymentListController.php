<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingPaymentList;
use App\Models\MeetingPaymentItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MeetingPaymentListController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = MeetingPaymentList::query()
            ->with(['meeting.localCommittee', 'submitter', 'validator']);

        // Filtrer selon le rôle
        if ($user->hasRole(['secretaire', 'Secrétaire'])) {
            $query->whereHas('meeting.localCommittee', function($q) use ($user) {
                $q->where('locality_id', $user->locality_id);
            });
        } elseif ($user->hasRole(['sous-prefet', 'Sous-prefet'])) {
            $query->whereHas('meeting.localCommittee', function($q) use ($user) {
                $q->where('locality_id', $user->locality_id);
            });
        }

        $paymentLists = $query->latest()->paginate(10);

        return Inertia::render('MeetingPayments/Lists/Index', [
            'paymentLists' => $paymentLists
        ]);
    }

    public function create(Meeting $meeting)
    {
        // Vérifier si la réunion est confirmée
        if ($meeting->status !== 'confirmed') {
            return redirect()->back()->with('error', 'La réunion doit être confirmée pour créer une liste de paiement.');
        }

        // Vérifier si on est au moins 2 jours avant la réunion
        if (Carbon::parse($meeting->scheduled_date)->subDays(2)->isPast()) {
            return redirect()->back()->with('error', 'La liste de paiement doit être créée au moins 2 jours avant la réunion.');
        }

        // Vérifier si une liste existe déjà
        if ($meeting->paymentList()->exists()) {
            return redirect()->back()->with('error', 'Une liste de paiement existe déjà pour cette réunion.');
        }

        // Charger les participants attendus
        $meeting->load(['attendees' => function($query) {
            $query->where('is_expected', true);
        }]);

        return Inertia::render('MeetingPayments/Lists/Create', [
            'meeting' => $meeting
        ]);
    }

    public function store(Request $request, Meeting $meeting)
    {
        // Vérifier si la réunion est confirmée
        if ($meeting->status !== 'confirmed') {
            return redirect()->back()->with('error', 'La réunion doit être confirmée pour créer une liste de paiement.');
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

        foreach ($validated['attendees'] as $attendee) {
            MeetingPaymentItem::create([
                'meeting_payment_list_id' => $paymentList->id,
                'attendee_id' => $attendee['id'],
                'amount' => $attendee['amount'],
                'role' => $attendee['role'],
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

        return Inertia::render('MeetingPayments/Lists/Show', [
            'paymentList' => $paymentList
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

    public function validate(Request $request, MeetingPaymentList $paymentList)
    {
        if ($paymentList->status !== 'submitted') {
            return redirect()->back()->with('error', 'Cette liste ne peut pas être validée.');
        }

        if (!Auth::user()->hasRole(['sous-prefet', 'Sous-prefet'])) {
            return redirect()->back()->with('error', 'Vous n\'avez pas les droits pour valider cette liste.');
        }

        $paymentList->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => Auth::id(),
        ]);

        // TODO: Envoyer une notification au secrétaire

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
} 