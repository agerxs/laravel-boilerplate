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
use App\Models\MeetingPaymentList;
use App\Models\MeetingPaymentItem;
use App\Imports\MeetingsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Models\BulkImport;

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
        if ($user->hasRole(['sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire'])) {
            // Pour les autres (présidents et secrétaires), montrer uniquement les réunions de leur localité
            $query->whereHas('localCommittee', function($q) use ($user) {
                $q->where('locality_id', $user->locality_id);
            });
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
        $allowedColumns = ['title', 'scheduled_date', 'status', 'updated_at'];
        
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
                    'updated_at' => $meeting->updated_at,
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
            // Pour les présidents et secrétaires, montrer uniquement les comités de leur localité
            $query->where('locality_id', $user->locality_id);
        }

        $localCommittees = $query->get();
        
        return Inertia::render('Meetings/Create', [
            'localCommittees' => $localCommittees,
        ]);
    }

    public function createMultiple(Request $request)
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
            // Pour les présidents et secrétaires, montrer uniquement les comités de leur localité
            $query->where('locality_id', $user->locality_id);
        }

        $localCommittees = $query->get();
        
        return Inertia::render('Meetings/CreateMultiple', [
            'localCommittees' => $localCommittees,
            'flash' => [
                'imported_meetings' => session('imported_meetings'),
                'selected_committee' => session('selected_committee'),
                'success' => session('success'),
            ],
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
            
            // Récupérer le président associé à cette localité avec son profil complet
            $sousPrefet = User::role('sous-prefet')
                ->where('locality_id', $committee->locality_id)
                ->first();
            
            // Récupérer le secrétaire associé à cette localité avec son profil complet
            $secretaire = User::role('Secrétaire')
                ->where('locality_id', $committee->locality_id)
                ->first();
            
            // Débogage - Vérifier si le président existe pour cette localité
            \Log::info('Président pour locality_id ' . $committee->locality_id, [
                'exists' => $sousPrefet ? 'oui' : 'non',
                'id' => $sousPrefet ? $sousPrefet->id : null,
                'name' => $sousPrefet ? $sousPrefet->name : null,
            ]);
            
            // Vérifier si le président et le secrétaire existent déjà dans les membres du comité
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
            'description' => 'nullable|string',
            //'target_enrollments' => 'required|integer|min:0',
            //'actual_enrollments' => 'required|integer|min:0|lte:target_enrollments',
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
       // $meeting->description = $validated['description'] ?? null;
        //$meeting->target_enrollments = $validated['target_enrollments'];
        //$meeting->actual_enrollments = $validated['actual_enrollments'];
        $meeting->status = 'scheduled';
        $meeting->save();
        
        // Récupérer le comité local avec ses villages et représentants
        $localCommittee = LocalCommittee::with(['locality.children.representatives'])->find($validated['local_committee_id']);
        
        // Pour chaque village du comité local
        if ($localCommittee->locality) {
            foreach ($localCommittee->locality->children as $village) {
                foreach ($village->representatives as $representative) {
                    $meeting->attendees()->create([
                        'representative_id' => $representative->id,
                        'name' => $representative->first_name . ' ' . $representative->last_name,
                        'role' => $representative->role,
                        'phone' => $representative->phone,
                        'localite_id' => $village->id
                    ]);
                }
            }
        }

        return redirect()->route('meetings.index')->with('success', 'Réunion créée avec succès');
    }

    public function storeMultiple(Request $request)
    {
        $validated = $request->validate([
            'local_committee_id' => 'required|exists:local_committees,id',
            'meetings' => 'required|array|min:1',
            'meetings.*.title' => 'required|string|max:255',
            'meetings.*.scheduled_date' => 'required|date',
            'meetings.*.scheduled_time' => 'required',
            'meetings.*.location' => 'required|string|max:255',
        ]);
        
        $createdMeetings = [];
        $errors = [];
        
        // Créer chaque réunion
        foreach ($validated['meetings'] as $index => $meetingData) {
            try {
                // Combiner la date et l'heure
                $scheduledDateTime = $meetingData['scheduled_date'] . ' ' . $meetingData['scheduled_time'];
                
                // Créer la réunion
                $meeting = new Meeting();
                $meeting->title = $meetingData['title'];
                $meeting->local_committee_id = $validated['local_committee_id'];
                $meeting->scheduled_date = $scheduledDateTime;
                $meeting->location = $meetingData['location'];
                $meeting->status = 'scheduled';
                $meeting->created_by = auth()->id();
                $meeting->save();
                
                // Récupérer le comité local avec ses villages et représentants
                $localCommittee = LocalCommittee::with(['locality.children.representatives'])->find($validated['local_committee_id']);
                
                // Pour chaque village du comité local
                if ($localCommittee->locality) {
                    foreach ($localCommittee->locality->children as $village) {
                        foreach ($village->representatives as $representative) {
                            $meeting->attendees()->create([
                                'representative_id' => $representative->id,
                                'name' => $representative->first_name . ' ' . $representative->last_name,
                                'role' => $representative->role,
                                'phone' => $representative->phone,
                                'localite_id' => $village->id
                            ]);
                        }
                    }
                }
                
                $createdMeetings[] = $meeting;
                
            } catch (\Exception $e) {
                $errors[] = "Erreur lors de la création de la réunion " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        if (count($errors) > 0) {
            return redirect()->back()
                ->withErrors($errors)
                ->withInput();
        }
        
        // Mettre à jour le bulk import avec les informations finales
        $bulkImport = BulkImport::where('user_id', auth()->id())
            ->where('local_committee_id', $validated['local_committee_id'])
            ->where('status', 'completed')
            ->latest()
            ->first();
            
        if ($bulkImport) {
            $bulkImport->update([
                'meetings_created' => count($createdMeetings),
            ]);
        }
        
        $successMessage = count($createdMeetings) . ' réunion(s) créée(s) avec succès';
        return redirect()->route('meetings.index')->with('success', $successMessage);
    }

    public function importMeetings(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
            'local_committee_id' => 'required|exists:local_committees,id',
        ]);

        try {
            // Créer l'enregistrement d'import
            $bulkImport = BulkImport::create([
                'user_id' => auth()->id(),
                'local_committee_id' => $request->local_committee_id,
                'import_type' => 'meetings',
                'original_filename' => $request->file('file')->getClientOriginalName(),
                'file_type' => $request->file('file')->getMimeType(),
                'file_size' => $request->file('file')->getSize(),
                'status' => 'processing',
            ]);

            // Stocker le fichier
            $filename = time() . '_' . $request->file('file')->getClientOriginalName();
            $filePath = $request->file('file')->storeAs('bulk_imports', $filename, 'public');
            
            $bulkImport->update([
                'file_path' => $filePath,
            ]);

            $import = new MeetingsImport();
            Excel::import($import, $request->file('file'));

            $importedData = $import->getData();
            $errors = $import->getErrors();

            if (empty($importedData)) {
                $bulkImport->update([
                    'status' => 'failed',
                    'error_message' => 'Aucune donnée valide trouvée dans le fichier.'
                ]);

                return redirect()->back()
                    ->withErrors(['file' => 'Aucune donnée valide trouvée dans le fichier.'])
                    ->withInput();
            }

            if (!empty($errors)) {
                $bulkImport->update([
                    'status' => 'failed',
                    'error_message' => 'Erreurs dans le fichier: ' . implode(', ', $errors)
                ]);

                return redirect()->back()
                    ->withErrors(['file' => 'Erreurs dans le fichier: ' . implode(', ', $errors)])
                    ->withInput();
            }

            // Mettre à jour l'import avec les données
            $bulkImport->update([
                'import_data' => $importedData,
                'status' => 'completed',
            ]);

            // Retourner les données importées pour pré-remplir le formulaire
            return redirect()->back()
                ->with('imported_meetings', $importedData)
                ->with('selected_committee', $request->local_committee_id)
                ->with('bulk_import_id', $bulkImport->id)
                ->with('success', count($importedData) . ' réunions importées et prêtes à être créées');

        } catch (\Exception $e) {
            // En cas d'erreur, mettre à jour le statut
            if (isset($bulkImport)) {
                $bulkImport->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            return redirect()->back()
                ->withErrors(['file' => 'Erreur lors de l\'import: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function storeMultipleWithAttachments(Request $request)
    {
        $validated = $request->validate([
            'local_committee_id' => 'required|exists:local_committees,id',
            'meetings' => 'required|array|min:1',
            'meetings.*.title' => 'required|string|max:255',
            'meetings.*.scheduled_date' => 'required|date',
            'meetings.*.scheduled_time' => 'required',
            'meetings.*.location' => 'required|string|max:255',
            'common_attachments.*' => 'nullable|file|max:10240', // 10MB max
            'bulk_import_id' => 'nullable|exists:bulk_imports,id',
        ]);
        
        $createdMeetings = [];
        $errors = [];
        $attachmentsInfo = [];
        
        // Traiter les pièces jointes communes
        if ($request->hasFile('common_attachments')) {
            foreach ($request->file('common_attachments') as $attachment) {
                $attachmentsInfo[] = [
                    'original_name' => $attachment->getClientOriginalName(),
                    'file_type' => $attachment->getMimeType(),
                    'size' => $attachment->getSize(),
                ];
            }
        }
        
        // Créer chaque réunion
        foreach ($validated['meetings'] as $index => $meetingData) {
            try {
                // Combiner la date et l'heure
                $scheduledDateTime = $meetingData['scheduled_date'] . ' ' . $meetingData['scheduled_time'];
                
                // Créer la réunion
                $meeting = new Meeting();
                $meeting->title = $meetingData['title'];
                $meeting->local_committee_id = $validated['local_committee_id'];
                $meeting->scheduled_date = $scheduledDateTime;
                $meeting->location = $meetingData['location'];
                $meeting->status = 'scheduled';
                $meeting->created_by = auth()->id();
                $meeting->bulk_import_id = $validated['bulk_import_id'] ?? null;
                $meeting->save();
                
                // Gérer les pièces jointes communes pour cette réunion
                if ($request->hasFile('common_attachments')) {
                    foreach ($request->file('common_attachments') as $attachment) {
                        $filename = time() . '_' . $attachment->getClientOriginalName();
                        $path = $attachment->storeAs('meeting_attachments', $filename, 'public');
                        
                        $meeting->attachments()->create([
                            'title' => $attachment->getClientOriginalName(),
                            'original_name' => $attachment->getClientOriginalName(),
                            'file_path' => $path,
                            'file_type' => $attachment->getMimeType(),
                            'nature' => 'document_justificatif',
                            'size' => $attachment->getSize(),
                            'uploaded_by' => auth()->id(),
                        ]);
                    }
                }
                
                // Récupérer le comité local avec ses villages et représentants
                $localCommittee = LocalCommittee::with(['locality.children.representatives'])->find($validated['local_committee_id']);
                
                // Pour chaque village du comité local
                if ($localCommittee->locality) {
                    foreach ($localCommittee->locality->children as $village) {
                        foreach ($village->representatives as $representative) {
                            $meeting->attendees()->create([
                                'representative_id' => $representative->id,
                                'name' => $representative->first_name . ' ' . $representative->last_name,
                                'role' => $representative->role,
                                'phone' => $representative->phone,
                                'localite_id' => $village->id
                            ]);
                        }
                    }
                }
                
                $createdMeetings[] = $meeting;
                
            } catch (\Exception $e) {
                $errors[] = "Erreur lors de la création de la réunion " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        if (count($errors) > 0) {
            return redirect()->back()
                ->withErrors($errors)
                ->withInput();
        }
        
        // Mettre à jour le bulk import avec les informations finales
        if (isset($validated['bulk_import_id'])) {
            $bulkImport = BulkImport::find($validated['bulk_import_id']);
            if ($bulkImport) {
                $bulkImport->update([
                    'meetings_created' => count($createdMeetings),
                    'attachments_count' => count($attachmentsInfo),
                    'attachments_info' => $attachmentsInfo,
                ]);
            }
        } else {
            // Si pas de bulk_import_id, chercher le dernier import de l'utilisateur pour ce comité
            $bulkImport = BulkImport::where('user_id', auth()->id())
                ->where('local_committee_id', $validated['local_committee_id'])
                ->where('status', 'completed')
                ->latest()
                ->first();
                
            if ($bulkImport) {
                $bulkImport->update([
                    'meetings_created' => count($createdMeetings),
                    'attachments_count' => count($attachmentsInfo),
                    'attachments_info' => $attachmentsInfo,
                ]);
            }
        }
        
        $successMessage = count($createdMeetings) . ' réunion(s) créée(s) avec succès';
        return redirect()->route('meetings.index')->with('success', $successMessage);
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
     * Valider une réunion (réservé aux présidents)
     */
    public function validateMeeting(Meeting $meeting)
    {
        // Vérifier si l'utilisateur est un président ou admin
        if (!Auth::user()->hasRole(['sous-prefet', 'Sous-prefet', 'admin', 'Admin'])) {
            return response()->json([
                'message' => 'Vous n\'avez pas les droits pour valider cette réunion.'
            ], 403);
        }

        // Vérifier si la réunion peut être validée
        if ($meeting->status !== 'completed') {
            return response()->json([
                'message' => 'Cette réunion ne peut pas être validée car elle n\'est pas marquée comme terminée.'
            ], 400);
        }

        // Mettre à jour le statut
        $meeting->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => Auth::id(),
        ]);

        // Créer une liste de paiement si elle n'existe pas
        if (!$meeting->paymentList()->exists()) {
            $paymentList = MeetingPaymentList::create([
                'meeting_id' => $meeting->id,
                'submitted_at' => now(),
                'status' => 'submitted',
                'submitted_by' => Auth::id(),
                'total_amount' => 0,
            ]);

            // Récupérer le comité local avec sa localité
            $localCommittee = LocalCommittee::with('locality')->find($meeting->local_committee_id);
            
            // Compter le nombre de réunions validées pour cette localité
            $validatedMeetingsCount = Meeting::whereHas('localCommittee', function($query) use ($localCommittee) {
                $query->where('locality_id', $localCommittee->locality_id);
            })
            ->where('status', 'validated')
            ->count();

            // Vérifier si c'est la 2ème réunion validée
            $isSecondValidatedMeeting = ($validatedMeetingsCount % 2) === 0;
            
            // Ajouter le président s'il existe et si c'est la 2ème réunion validée
            if ($isSecondValidatedMeeting) {
                $sousPrefet = User::role(['sous-prefet', 'Sous-prefet'])
                    ->where('locality_id', $localCommittee->locality_id)
                    ->first();
                
                if ($sousPrefet) {
                    MeetingPaymentItem::create([
                        'meeting_payment_list_id' => $paymentList->id,
                        'attendee_id' => null,
                        'amount' => MeetingPaymentList::SUB_PREFET_AMOUNT,
                        'role' => 'sous-prefet',
                        'payment_status' => 'pending',
                        'name' => $sousPrefet->name,
                        'phone' => $sousPrefet->phone
                    ]);
                }

                // Ajouter le secrétaire s'il existe
                $secretaire = User::role(['secretaire', 'Secrétaire'])
                    ->where('locality_id', $localCommittee->locality_id)
                    ->first();
                
                if ($secretaire) {
                    MeetingPaymentItem::create([
                        'meeting_payment_list_id' => $paymentList->id,
                        'attendee_id' => null,
                        'amount' => MeetingPaymentList::SECRETARY_AMOUNT,
                        'role' => 'secretaire',
                        'payment_status' => 'pending',
                        'name' => $secretaire->name,
                        'phone' => $secretaire->phone
                    ]);
                }
            }

            // Créer les éléments de paiement pour chaque participant présent
            foreach ($meeting->attendees()->where('is_present', true)->get() as $attendee) {
                info($attendee);
                $amount = 0;
                switch ($attendee->role) {
                    case 'sous-prefet':
                        $amount = MeetingPaymentList::SUB_PREFET_AMOUNT;
                        break;
                    case 'secretaire':
                        $amount = MeetingPaymentList::SECRETARY_AMOUNT;
                        break;
                    default :
                        $amount = MeetingPaymentList::PARTICIPANT_AMOUNT;
                        break;
                }

                if ($amount > 0) {
                    MeetingPaymentItem::create([
                        'meeting_payment_list_id' => $paymentList->id,
                        'attendee_id' => $attendee->id,
                        'amount' => $amount,
                        'role' => $attendee->role,
                        'payment_status' => 'pending',
                        'name' => $attendee->name,
                        'phone' => $attendee->phone
                    ]);
                }
            }

            // Mettre à jour le montant total
            $paymentList->update([
                'total_amount' => $paymentList->paymentItems->sum('amount')
            ]);
        }
        // Soumettre la liste de paiement si elle existe
        else {
            $paymentList = $meeting->paymentList;
            if ($paymentList->status === 'draft') {
                $paymentList->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'submitted_by' => Auth::id(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Réunion validée avec succès et liste de paiement générée.',
            'meeting' => $meeting
        ]);
    }

    /**
     * Invalider une réunion (réservé aux présidents)
     */
    public function invalidate(Meeting $meeting)
    {
        // Permettre aux administrateurs ainsi qu'aux présidents d'invalider
        if (Auth::user()->role !== 'sous-prefet' && !Auth::user()->hasRole('admin')) {
            abort(403, 'Seuls les présidents et les administrateurs peuvent invalider les réunions');
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
     * Dépublier une réunion (revenir à l'état planifié).
     */
    public function unpublish(Meeting $meeting)
    {
        // Vérifier si la réunion peut être dépublicée
        if ($meeting->status !== 'completed') {
            return back()->with('error', 'Seules les réunions publiées peuvent être dépublicées.');
        }
        
        // Vérifier que la réunion n'a pas encore été validée ou invalidée par le président
        if ($meeting->validated_at || $meeting->invalidated_at) {
            return back()->with('error', 'Cette réunion ne peut plus être dépublicée car elle a déjà été validée ou invalidée par le président.');
        }
        
        $meeting->update([
            'status' => 'scheduled',
            'completed_at' => null,
            'completed_by' => null
        ]);
        
        return response()->json(['success' => true, 'message' => 'La réunion a été dépublicée avec succès']);
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
        
        // Vérifier si l'utilisateur a le droit de modifier les enrôlements
       /* if (!Gate::allows('updateEnrollments', $meeting)) {
            return response()->json(['error' => 'Vous n\'êtes pas autorisé à modifier les enrôlements de cette réunion.'], 403);
        }*/
        
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