<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Notifications\AgendaUpdated;
use App\Services\MeetingExportService;
use App\Models\LocalCommittee;
use App\Services\MeetingInvitationService;
use Illuminate\Support\Facades\Mail;
use App\Mail\AgendaUpdatedMail;
use App\Notifications\MeetingCancelled;
use App\Mail\MeetingCancelledMail;
use App\Notifications\MeetingInvitation;
use App\Mail\GuestMeetingInvitation;
use App\Models\MeetingAttendee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MeetingController extends Controller
{
    public function __construct(
        protected MeetingInvitationService $invitationService
    ) {}

    public function index(Request $request)
    {
        $query = Meeting::query()
            ->with('localCommittee.locality')
            ->select('meetings.*');
        
        // Filtrer les réunions en fonction de la localité de l'utilisateur
        $user = auth()->user();
        
        // Filtrer par localité si l'utilisateur est un préfet ou un secrétaire
        if ($user->hasRole(['prefet', 'Prefet', 'sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire'])) {
            if ($user->hasRole(['prefet', 'Prefet'])) {
                // Pour les préfets, montrer les réunions de leur département et des sous-préfectures associées
                $query->whereHas('localCommittee.locality', function($q) use ($user) {
                    $q->where('id', $user->locality_id)
                      ->orWhere('parent_id', $user->locality_id);
                });
            } else {
                // Pour les autres (sous-préfets et secrétaires), montrer uniquement les réunions de leur localité
                $query->whereHas('localCommittee', function($q) use ($user) {
                    $q->where('locality_id', $user->locality_id);
                });
            }
        }
        
        // Appliquer la recherche si elle existe
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }
        
        // Appliquer le tri
        $sortColumn = $request->input('sort', 'scheduled_date');
        $direction = $request->input('direction', 'desc');
        
        // Valider la colonne de tri pour éviter les injections SQL
        $allowedColumns = ['title', 'scheduled_date', 'status'];
        
        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $direction);
        } else if ($sortColumn === 'local_committee') {
            // Tri spécial pour le comité local (nécessite un join)
            $query->join('local_committees', 'meetings.local_committee_id', '=', 'local_committees.id')
                  ->join('localities', 'local_committees.locality_id', '=', 'localities.id')
                  ->orderBy('localities.name', $direction)
                  ->select('meetings.*'); // Important pour éviter les conflits de colonnes
        }
        
        $meetings = $query->paginate(10)
            ->withQueryString()
            ->through(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'scheduled_date' => $meeting->scheduled_date,
                    'status' => $meeting->status,
                    'locality_name' => $meeting->localCommittee->locality->name ?? 'Non défini',
                ];
            });
        
        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings,
            'filters' => [
                'search' => $request->input('search', ''),
                'sort' => $sortColumn,
                'direction' => $direction
            ]
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $query = LocalCommittee::query();

        if ($user->hasRole(['prefet', 'Prefet'])) {
            // Pour les préfets, montrer les comités de leur département et sous-préfectures
            $query->whereHas('locality', function ($q) use ($user) {
                $q->where('id', $user->locality_id)
                  ->orWhere('parent_id', $user->locality_id);
            });
        } elseif ($user->hasRole(['sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire'])) {
            // Pour les sous-préfets et secrétaires, montrer uniquement les comités de leur localité
            $query->where('locality_id', $user->locality_id);
        }

        $localCommittees = $query->get();
        
        return Inertia::render('Meetings/Create', [
            'localCommittees' => $localCommittees,
        ]);
    }

    public function show(Meeting $meeting)
    {
        // Charger la relation localCommittee avec sa localité et les villages associés
        // Charger également les relations prevalidator et validator
        $meeting->load(['localCommittee.locality', 'prevalidator', 'validator', 'minutes', 'attachments']);
        
        // Charger l'utilisateur avec ses rôles
        $user = auth()->user()->load('roles');
        
        // Vérifier si le comité local existe
        if (!$meeting->localCommittee || !$meeting->localCommittee->locality) {
            // Si le comité local ou sa localité n'existe pas, créer un objet avec des valeurs par défaut
            $committee = [
                'id' => null,
                'name' => 'Non défini',
                'locality' => [
                    'id' => null,
                    'name' => 'Non défini',
                    'children' => [] // Ajouter un tableau vide pour les villages
                ]
            ];
        } else {
            // Charger le comité local avec toutes ses relations
            $committee = LocalCommittee::with(['locality.children.representatives', 'members.user'])->findOrFail($meeting->local_committee_id);
            
            // S'assurer que les villages (children) existent
            if (!$committee->locality->children) {
                $committee->locality->children = [];
            }
            
            // Récupérer le sous-préfet associé à cette localité avec son profil complet
            $sousPrefet = User::role('sous-prefet')
                ->where('locality_id', $committee->locality_id)
                ->first();
            
            // Récupérer le secrétaire associé à cette localité avec son profil complet
            $secretaire = User::role('Secrétaire')
                ->where('locality_id', $committee->locality_id)
                ->first();
            
            // Débogage - Vérifier si le sous-préfet existe pour cette localité
            \Log::info('Sous-préfet pour locality_id ' . $committee->locality_id, [
                'exists' => $sousPrefet ? 'oui' : 'non',
                'id' => $sousPrefet ? $sousPrefet->id : null,
                'name' => $sousPrefet ? $sousPrefet->name : null,
            ]);
            
            // Vérifier si le sous-préfet et le secrétaire existent déjà dans les membres du comité
            $hasSousPrefet = false;
            $hasSecretaire = false;
            
            // Parcourir les membres existants pour vérifier
            foreach ($committee->members as $member) {
                if ($member->user && $sousPrefet && $member->user->id === $sousPrefet->id) {
                    $hasSousPrefet = true;
                }
                if ($member->user && $secretaire && $member->user->id === $secretaire->id) {
                    $hasSecretaire = true;
                }
            }
                
            // Ajouter ces utilisateurs à la liste des membres du comité s'ils existent et ne sont pas déjà présents
            if ($sousPrefet && !$hasSousPrefet) {
                $committee->members[] = (object)[
                    'id' => 'sp_'.$sousPrefet->id,
                    'user' => $sousPrefet,
                    'role' => 'sous-prefet'
                ];
            }
            
            if ($secretaire && !$hasSecretaire) {
                $committee->members[] = (object)[
                    'id' => 'sec_'.$secretaire->id,
                    'user' => $secretaire,
                    'role' => 'secretaire'
                ];
            }
        }
        
        return Inertia::render('Meetings/Show', [
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'scheduled_date' => $meeting->scheduled_date,
                'status' => $meeting->status,
                'locality_name' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
                'local_committee_id' => $meeting->local_committee_id,
                'local_committee' => $committee,
                'attendees' => $meeting->attendees,
                'prevalidated_at' => $meeting->prevalidated_at,
                'prevalidated_by' => $meeting->prevalidated_by,
                'prevalidator' => $meeting->prevalidator,
                'validated_at' => $meeting->validated_at,
                'validated_by' => $meeting->validated_by,
                'validator' => $meeting->validator,
                'validation_comments' => $meeting->validation_comments,
                'location' => $meeting->location,
                'minutes' => $meeting->minutes,
                'attachments' => $meeting->attachments,
                'target_enrollments' => $meeting->target_enrollments,
                'actual_enrollments' => $meeting->actual_enrollments
            ],
            'user' => $user
        ]);
    }

    public function edit(Meeting $meeting)
    {
        $meeting->load(['localCommittee.locality']);
        
        $localCommittees = LocalCommittee::all();

        return Inertia::render('Meetings/Edit', [
            'meeting' => $meeting,
            'localCommittees' => $localCommittees,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'local_committee_id' => 'required|exists:local_committees,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'location' => 'required|string|max:255',
            //'agenda' => 'nullable|string',
            'representatives' => 'nullable|array',
        ]);
        
        // Combiner la date et l'heure
        $scheduledDateTime = $validated['scheduled_date'] . ' ' . $validated['scheduled_time'];
        
        // Créer la réunion
        $meeting = new Meeting();
        $meeting->title = $validated['title'];
        $meeting->local_committee_id = $validated['local_committee_id'];
        $meeting->scheduled_date = $scheduledDateTime;
        $meeting->location = $validated['location'];
        //$meeting->agenda = $validated['agenda'];
        $meeting->status = 'scheduled';
        $meeting->save();
        
        // Traiter les représentants
        if ($request->has('representatives')) {
            $representatives = $request->input('representatives');
            
            foreach ($representatives as $villageId => $villageReps) {
                foreach ($villageReps as $rep) {
                    if (isset($rep['is_expected']) && $rep['is_expected']) {
                        $meetingAttendee = new MeetingAttendee();
                        $meetingAttendee->meeting_id = $meeting->id;
                        $meetingAttendee->localite_id = $villageId;
                        $meetingAttendee->representative_id = $rep['id'] ?? null;
                        $meetingAttendee->name = $rep['name'];
                        $meetingAttendee->phone = $rep['phone'] ?? '';
                        $meetingAttendee->role = $rep['role'] ?? '';
                        $meetingAttendee->is_expected = true;
                        $meetingAttendee->attendance_status = 'expected';
                        $meetingAttendee->save();
                    }
                }
            }
        }
        
        return redirect()->route('meetings.show', $meeting->id)
            ->with('success', 'Réunion planifiée avec succès');
    }

    public function update(Request $request, Meeting $meeting)
    {
        // Vérifier si la réunion peut être modifiée
        if (!$meeting->canBeEdited()) {
            return redirect()->route('meetings.show', $meeting->id)
                ->with('error', 'Cette réunion ne peut pas être modifiée car elle a déjà été prévalidée ou validée.');
        }
        
        $validated = $request->validate([
            //'title' => 'required|string|max:255',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'localCommittee' => 'required|exists:local_committees,id',
        ]);

        $validated['local_committee_id'] = $validated['localCommittee'];
        unset($validated['localCommittee']);

        $meeting->update($validated);

        return redirect()->route('meetings.index')->with('success', 'Réunion mise à jour avec succès');
    }

    public function export(Meeting $meeting, MeetingExportService $exportService)
    {
        return $exportService->exportToPdf($meeting);
    }

    public function cancel(Meeting $meeting)
    {
        $meeting->update(['status' => 'cancelled']);
        
        return response()->json(['status' => 'success']);
    }

    public function notify(Meeting $meeting)
    {
        try {
            // Notifier les participants qui sont des utilisateurs
            $meeting->participants()
                ->whereNotNull('user_id')
                ->with('user')
                ->get()
                ->each(function ($participant) use ($meeting) {
                    if ($participant->user) {
                        $participant->user->notify(new MeetingInvitation($meeting));
                    }
                });

            // Envoyer des emails aux invités externes
            $meeting->participants()
                ->whereNotNull('guest_email')
                ->get()
                ->each(function ($participant) use ($meeting) {
                    Mail::to($participant->guest_email)
                        ->send(new GuestMeetingInvitation($meeting, $participant->guest_name));
                });

            return response()->json([
                'message' => 'Notifications envoyées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'envoi des notifications'
            ], 500);
        }
    }

    public function reschedule(Request $request, $meetingId)
    {
        $request->validate([
            'date' => 'required|date',
            'reason' => 'required|string|max:255',
        ]);

        $meeting = Meeting::findOrFail($meetingId);
        
        // Vérifier si la réunion peut être reprogrammée
        if (!$meeting->canBeRescheduled()) {
            return response()->json([
                'message' => 'Cette réunion ne peut pas être reprogrammée car elle a été prévalidée, validée, annulée ou terminée.'
            ], 403);
        }
        
        $meeting->scheduled_date = $request->input('date');
        $meeting->reschedule_reason = $request->input('reason');
        $meeting->save();

        return response()->json(['message' => 'La réunion a été reportée avec succès.']);
    }

    public function showRescheduleForm($meetingId)
    {
        $meeting = Meeting::findOrFail($meetingId);

        return Inertia::render('Meetings/Reschedule', [
            'meetingId' => $meeting->id,
            'meeting' => [
                'title' => $meeting->title,
                'scheduled_date' => $meeting->scheduled_date,
                'locality_name' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
            ],
        ]);
    }

    public function getRepresentatives(Meeting $meeting)
    {
        // Récupérer les représentants déjà enregistrés pour cette réunion
        $representatives = $meeting->attendees()->get();
        
        return response()->json([
            'representatives' => $representatives
        ]);
    }
    
    public function saveRepresentatives(Request $request, Meeting $meeting)
    {
        $request->validate([
            'representatives' => 'required|array',
        ]);
        
        // Supprimer les anciens représentants
        MeetingAttendee::where('meeting_id', $meeting->id)->delete();
        
        // Ajouter les nouveaux représentants
        $representatives = $request->input('representatives');
        
        foreach ($representatives as $villageId => $villageReps) {
            foreach ($villageReps as $rep) {
            
                if (isset($rep['is_expected']) && $rep['is_expected']) {
                    $attendee = new MeetingAttendee();
                    $attendee->meeting_id = $meeting->id;
                    $attendee->localite_id = $villageId;
                    $attendee->representative_id = $rep['representative_id'] ?? null;
                    $attendee->name = $rep['name'];
                    $attendee->phone = $rep['phone'] ?? '';
                    $attendee->role = $rep['role'] ?? '';
                    $attendee->is_expected = true;
                    $attendee->is_present = $rep['is_present'] ?? false;
                    $attendee->attendance_status = 'expected';
                    $attendee->save();
                }
            }
        }
        
        return response()->json([
            'message' => 'Représentants enregistrés avec succès'
        ]);
    }

    /**
     * Prévalider une réunion (réservé aux secrétaires)
     */
    public function prevalidate(Meeting $meeting)
    {

        // Permettre aux administrateurs ainsi qu'aux secrétaires de prévalider
        if (!Auth::user()->hasRole('secretaire') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Seuls les secrétaires et les administrateurs peuvent prévalider les réunions');
        }

        if ($meeting->status !== 'scheduled' && $meeting->status !== 'planned' && $meeting->status !== 'completed') {
            return back()->with('error', 'Seules les réunions planifiées peuvent être prévalidées');
        }
        $meeting->update([
            'status' => 'prevalidated',
            'prevalidated_at' => now(),
            'prevalidated_by' => Auth::id()
        ]);
        //dd($meeting);
        return back()->with('success', 'La réunion a été prévalidée avec succès');
    }

    /**
     * Valider une réunion (réservé aux sous-préfets)
     */
    public function validate(Meeting $meeting)
    {
        // Permettre aux administrateurs ainsi qu'aux sous-préfets de valider
        if (!Auth::user()->hasRole('sous-prefet') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Seuls les sous-préfets et les administrateurs peuvent valider les réunions');
        }

        if ($meeting->status !== 'prevalidated') {
            return back()->with('error', 'Seules les réunions prévalidées peuvent être validées');
        }

        $meeting->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => Auth::id()
        ]);

        return back()->with('success', 'La réunion a été validée avec succès');
    }

    /**
     * Invalider une réunion (réservé aux sous-préfets)
     */
    public function invalidate(Meeting $meeting)
    {
        // Permettre aux administrateurs ainsi qu'aux sous-préfets d'invalider
        if (Auth::user()->role !== 'sous-prefet' && !Auth::user()->hasRole('admin')) {
            abort(403, 'Seuls les sous-préfets et les administrateurs peuvent invalider les réunions');
        }

        if (!in_array($meeting->status, ['prevalidated', 'validated'])) {
            return back()->with('error', 'Seules les réunions prévalidées ou validées peuvent être invalidées');
        }

        $meeting->update([
            'status' => 'scheduled',
            'prevalidated_at' => null,
            'prevalidated_by' => null,
            'validated_at' => null,
            'validated_by' => null
        ]);

        return back()->with('success', 'La réunion a été invalidée avec succès');
    }

    /**
     * Marquer une réunion comme terminée.
     */
    public function complete(Meeting $meeting)
    {
        // Vérifier si la réunion peut être marquée comme terminée
        if (!in_array($meeting->status, ['scheduled', 'prevalidated', 'validated', 'planned'])) {
            return back()->with('error', 'Cette réunion ne peut pas être marquée comme terminée car elle est déjà terminée ou annulée.');
        }
        
        $meeting->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => Auth::id()
        ]);
        
        return response()->json(['success' => true, 'message' => 'La réunion a été marquée comme terminée']);
    }

    /**
     * Mettre à jour les champs d'enrôlement d'une réunion.
     */
    public function updateEnrollments(Request $request, Meeting $meeting)
    {
        // Valider les données entrantes
        $validated = $request->validate([
            'target_enrollments' => 'required|integer|min:0',
            'actual_enrollments' => 'required|integer|min:0|lte:target_enrollments',
        ]);
        
        // Vérifier si l'utilisateur a le droit de modifier la réunion
            // Les utilisateurs avec le rôle admin sont autorisés, ainsi que ceux qui passent la vérification de Gate
        if (!Gate::allows('update', $meeting) && !auth()->user()->hasRole('admin')) {
            return response()->json(['error' => 'Vous n\'êtes pas autorisé à modifier cette réunion.'], 403);
        }
        
        // Mettre à jour les champs d'enrôlement
        $meeting->update([
            'target_enrollments' => $validated['target_enrollments'],
            'actual_enrollments' => $validated['actual_enrollments'],
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Les champs d\'enrôlement ont été mis à jour avec succès.',
            'data' => [
                'target_enrollments' => $meeting->target_enrollments,
                'actual_enrollments' => $meeting->actual_enrollments,
            ]
        ]);
    }

    public function confirm(Meeting $meeting)
    {
        // Vérifier si l'utilisateur est un secrétaire
        if (!Auth::user()->hasRole(['secretaire', 'Secrétaire'])) {
            return response()->json([
                'message' => 'Vous n\'avez pas les droits pour confirmer cette réunion.'
            ], 403);
        }

        // Vérifier si la réunion peut être confirmée
        if ($meeting->status !== 'scheduled') {
            return response()->json([
                'message' => 'Cette réunion ne peut pas être confirmée.'
            ], 400);
        }

        // Mettre à jour le statut
        $meeting->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Réunion confirmée avec succès.',
            'meeting' => $meeting
        ]);
    }
} 