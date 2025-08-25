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
use App\Services\MeetingSplitService;

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
        if ($user->hasRole(['president', 'president', 'secretaire', 'Secrétaire'])) {
            // Pour les autres (présidents et secrétaires), montrer uniquement les réunions de leur localité
            $query->whereHas('localCommittee', function($q) use ($user) {
                $q->where('locality_id', $user->locality_id);
            });
        }
        
        // Par défaut, ne montrer que les réunions parent (pas les sous-réunions)
        // Sauf si on filtre spécifiquement pour les sous-réunions
        if (!$request->has('hierarchy') || $request->input('hierarchy') !== 'sub') {
            $query->whereNull('parent_meeting_id');
        }
        
        // Appliquer la recherche si elle existe
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }
        
        // Appliquer le filtre de hiérarchie
        if ($request->has('hierarchy') && $request->input('hierarchy')) {
            $hierarchy = $request->input('hierarchy');
            
            switch ($hierarchy) {
                case 'parent':
                    // Réunions parent uniquement (qui ont des sous-réunions)
                    $query->whereNull('parent_meeting_id')
                          ->whereHas('subMeetings');
                    break;
                case 'sub':
                    // Sous-réunions uniquement
                    $query->whereNotNull('parent_meeting_id');
                    break;
                case 'normal':
                    // Réunions normales uniquement (ni parent ni sous-réunion)
                    $query->whereNull('parent_meeting_id')
                          ->whereDoesntHave('subMeetings');
                    break;
            }
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
                $meetingData = [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'scheduled_date' => $meeting->scheduled_date,
                    'status' => $meeting->status,
                    'locality_name' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
                    'updated_at' => $meeting->updated_at,
                    'parent_meeting_id' => $meeting->parent_meeting_id,
                    'sub_meetings_count' => $meeting->subMeetings()->count(),
                    'attendance_status' => $meeting->attendance_status,
                    'attendance_validated_at' => $meeting->attendance_validated_at,
                ];
                

                
                // Ajouter les sous-réunions si c'est une réunion parent
                if ($meeting->subMeetings()->count() > 0) {
                    // Recharger les sous-réunions depuis la base pour avoir les données à jour
                    $subMeetings = Meeting::where('parent_meeting_id', $meeting->id)
                        ->with('localCommittee.locality')
                        ->get();
                    
                    $meetingData['sub_meetings'] = $subMeetings->map(function ($subMeeting) {
                        
                        return [
                            'id' => $subMeeting->id,
                            'title' => $subMeeting->title,
                            'scheduled_date' => $subMeeting->scheduled_date,
                            'status' => $subMeeting->status,
                            'locality_name' => $subMeeting->localCommittee?->locality?->name ?? 'Non défini',
                            'updated_at' => $subMeeting->updated_at,
                            'parent_meeting_id' => $subMeeting->parent_meeting_id,
                            'attendance_status' => $subMeeting->attendance_status,
                            'attendance_validated_at' => $subMeeting->attendance_validated_at,
                        ];
                    })->toArray();
                }
                
                return $meetingData;
            });
        
         
        // Récupérer les comités locaux pour les filtres
        $user = auth()->user();
        $committeeQuery = LocalCommittee::query();

        if ($user->hasRole(['prefet', 'Prefet'])) {
            // Pour les préfets, montrer les comités de leur département et sous-préfectures
            $committeeQuery->whereHas('locality', function ($q) use ($user) {
                $q->where('id', $user->locality_id)
                  ->orWhere('parent_id', $user->locality_id);
            });
        } elseif ($user->hasRole(['president', 'President', 'secretaire', 'Secrétaire'])) {
            // Pour les présidents et secrétaires, montrer uniquement les comités de leur localité
            $committeeQuery->where('locality_id', $user->locality_id);
        }

        $localCommittees = $committeeQuery->get();

        // Définir les statuts de réunion disponibles
        $meetingStatuses = [
            ['value' => 'planned', 'label' => 'Planifiée'],
            ['value' => 'scheduled', 'label' => 'Programmée'],
            ['value' => 'completed', 'label' => 'Terminée'],
            ['value' => 'cancelled', 'label' => 'Annulée'],
            ['value' => 'rescheduled', 'label' => 'Reportée'],
        ];

        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings,
            'filters' => [
                'search' => $request->input('search', '') ?? '',
                'status' => $request->input('status', '') ?? '',
                'local_committee_id' => $request->input('local_committee_id', '') ?? '',
                'date_from' => $request->input('date_from', '') ?? '',
                'date_to' => $request->input('date_to', '') ?? '',
                'hierarchy' => $request->input('hierarchy', '') ?? '',
                'sort' => $sortColumn ?? 'scheduled_date',
                'direction' => $direction ?? 'desc'
            ],
            'localCommittees' => $localCommittees,
            'meetingStatuses' => $meetingStatuses,
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
        } elseif ($user->hasRole(['president', 'President', 'secretaire', 'Secrétaire'])) {
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
        } elseif ($user->hasRole(['president', 'President', 'secretaire', 'Secrétaire'])) {
            // Pour les présidents et secrétaires, montrer uniquement les comités de leur localité
            $query->where('locality_id', $user->locality_id);
        }

        $localCommittees = $query->get();
        
        // Récupérer le comité local de l'utilisateur connecté (pour les secrétaires)
        $userCommittee = null;
        if ($user->hasRole(['secretaire', 'Secrétaire'])) {
            $userCommittee = LocalCommittee::where('locality_id', $user->locality_id)->first();
        }
        
        return Inertia::render('Meetings/CreateMultiple', [
            'localCommittees' => $localCommittees,
            'userCommittee' => $userCommittee,
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
        $meeting->load([
            'localCommittee.locality', 
            'prevalidator', 
            'validator', 
            'minutes', 
            'attachments',
            'attendees.village',
            'attendees.representative',
            'subMeetings.attendees.locality',
            'subMeetings.attendees.representative',
            'villageResults'
        ]);
        
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
            $sousPrefet = User::role('president')
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
                    'role' => 'president'
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

        
        
        // Vérifier s'il reste des villages disponibles pour l'éclatement
        $availableVillages = [];
        if (in_array($meeting->status, ['planned', 'scheduled'])) {
            $splitService = app(MeetingSplitService::class);
            $availableVillages = $splitService->getAvailableVillages($meeting);
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
                'attendees' => $meeting->attendees->map(function ($attendee) {
                    // Log de débogage pour chaque participant
                    \Log::info('Données du participant', $attendee->debugInfo());
                    
                    return [
                        'id' => $attendee->id,
                        'name' => $attendee->name,
                        'phone' => $attendee->phone,
                        'role' => $attendee->role,
                        'village' => [
                            'id' => $attendee->localite_id,
                            'name' => $attendee->village ? $attendee->village->name : ($attendee->localite_id ? 'Village à identifier' : 'Pas de village associé')
                        ],
                        'is_expected' => $attendee->is_expected,
                        'is_present' => $attendee->is_present,
                        'attendance_status' => $attendee->attendance_status,
                        'replacement_name' => $attendee->replacement_name,
                        'replacement_phone' => $attendee->replacement_phone,
                        'replacement_role' => $attendee->replacement_role,
                        'arrival_time' => $attendee->arrival_time,
                        'comments' => $attendee->comments,
                        'payment_status' => $attendee->payment_status,
                        'presence_photo' => $attendee->presence_photo,
                        'presence_location' => $attendee->presence_location,
                        'presence_timestamp' => $attendee->presence_timestamp
                    ];
                }),
                'prevalidated_at' => $meeting->prevalidated_at,
                'prevalidated_by' => $meeting->prevalidated_by,
                'prevalidator' => $meeting->prevalidator,
                'validated_at' => $meeting->validated_at,
                'validated_by' => $meeting->validated_by,
                'validator' => $meeting->validator,
                'validation_comments' => $meeting->validation_comments,
                'attendance_validated_at' => $meeting->attendance_validated_at,
                'attendance_validated_by' => $meeting->attendance_validated_by,
                'attendance_status' => $meeting->attendance_status,
                'attendance_submitted_at' => $meeting->attendance_submitted_at,
                'attendance_submitted_by' => $meeting->attendance_submitted_by,
                'location' => $meeting->location,
                'minutes' => $meeting->minutes,
                'attachments' => $meeting->attachments,
                'target_enrollments' => $meeting->target_enrollments,
                'actual_enrollments' => $meeting->actual_enrollments,
                'sub_meetings' => $meeting->subMeetings->map(function ($subMeeting) {
                    return [
                        'id' => $subMeeting->id,
                        'title' => $subMeeting->title,
                        'scheduled_date' => $subMeeting->scheduled_date,
                        'location' => $subMeeting->location,
                        'status' => $subMeeting->status,
                        'target_enrollments' => $subMeeting->target_enrollments,
                        'actual_enrollments' => $subMeeting->actual_enrollments,
                        'attendees_count' => $subMeeting->attendees->count()
                    ];
                }),
                'available_villages_count' => count($availableVillages),
                'village_results' => $meeting->villageResults->map(function ($result) {
                    return [
                        'id' => $result->id,
                        'localite_id' => $result->localite_id,
                        'people_to_enroll_count' => $result->people_to_enroll_count,
                        'people_enrolled_count' => $result->people_enrolled_count,
                        'cmu_cards_available_count' => $result->cmu_cards_available_count,
                        'cmu_cards_distributed_count' => $result->cmu_cards_distributed_count,
                        'complaints_received_count' => $result->complaints_received_count,
                        'complaints_processed_count' => $result->complaints_processed_count,
                        'comments' => $result->comments,
                        'status' => $result->status,
                        'enrollment_rate' => $result->enrollment_rate,
                        'cmu_distribution_rate' => $result->cmu_distribution_rate,
                        'complaint_processing_rate' => $result->complaint_processing_rate,
                    ];
                })
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
        // Vérifier que la liste de présence n'est pas soumise ou validée
        if (in_array($meeting->attendance_status, ['submitted', 'validated'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible d\'annuler une réunion dont la liste de présence a été soumise ou validée'
            ], 400);
        }
        
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

    /**
     * Obtenir les villages disponibles pour l'éclatement d'une réunion
     */
    public function getAvailableVillagesForSplit(Meeting $meeting)
    {
        try {
            if (!$meeting->canBeSplit()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réunion ne peut pas être éclatée'
                ], 400);
            }

            $splitService = app(MeetingSplitService::class);
            $villages = $splitService->getAvailableVillages($meeting);

            return response()->json([
                'success' => true,
                'message' => 'Villages récupérés avec succès',
                'data' => [
                    'villages' => $villages,
                    'meeting' => $meeting->load(['localCommittee.locality'])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des villages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Éclater une réunion via API (pour Inertia)
     */
    public function splitMeetingApi(Request $request, Meeting $meeting)
    {
        try {
            $validated = $request->validate([
                'sub_meetings' => 'required|array|min:1',
                'sub_meetings.*.location' => 'required|string',
                'sub_meetings.*.villages' => 'required|array|min:1',
                'sub_meetings.*.villages.*.id' => 'required|exists:localite,id',
                'sub_meetings.*.villages.*.name' => 'required|string',
            ]);

            if (!$meeting->canBeSplit()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réunion ne peut pas être éclatée'
                ], 400);
            }

            $splitService = app(MeetingSplitService::class);
            $subMeetings = $splitService->splitMeeting($meeting, $validated['sub_meetings']);

            return response()->json([
                'success' => true,
                'message' => 'Réunion éclatée avec succès en ' . count($subMeetings) . ' sous-réunions',
                'data' => [
                    'parent_meeting' => $meeting->load(['subMeetings.attendees']),
                    'sub_meetings' => $subMeetings
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'éclatement de la réunion: ' . $e->getMessage()
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
     * Valider une réunion (réservé aux présidents)
     */
    public function validateMeeting(Meeting $meeting)
    {
        // Vérifier si l'utilisateur est un président ou admin
        if (!Auth::user()->hasRole(['president', 'President', 'admin', 'Admin'])) {
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
                $sousPrefet = User::role(['president', 'President'])
                    ->where('locality_id', $localCommittee->locality_id)
                    ->first();
                
                if ($sousPrefet) {
                    MeetingPaymentItem::create([
                        'meeting_payment_list_id' => $paymentList->id,
                        'attendee_id' => null,
                        'amount' => MeetingPaymentList::SUB_PREFET_AMOUNT,
                        'role' => 'president',
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
            foreach ($meeting->attendees()->where('attendance_status', 'present')->orWhere('attendance_status', 'replaced')->get() as $attendee) {
                $amount = 0;
                switch ($attendee->role) {
                    case 'president':
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
        if (Auth::user()->role !== 'president' && !Auth::user()->hasRole('admin')) {
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

    /**
     * Valider uniquement les présences d'une réunion
     */
    public function validateAttendance(Meeting $meeting)
    {
        // Vérifier si l'utilisateur est un sous-préfet ou admin
        if (!Auth::user()->hasRole(['president', 'President', 'admin', 'Admin'])) {
            return response()->json([
                'message' => 'Vous n\'avez pas les droits pour valider les présences de cette réunion.'
            ], 403);
        }

        // Vérifier si les présences peuvent être validées
        if ($meeting->attendance_status !== 'submitted') {
            return response()->json([
                'message' => 'Les présences doivent être soumises avant d\'être validées.'
            ], 400);
        }

        // Vérifier qu'il y a des participants présents
        $presentAttendees = $meeting->attendees()->where('attendance_status', 'present')->orWhere('attendance_status', 'replaced')->count();
        if ($presentAttendees === 0) {
            return response()->json([
                'message' => 'Impossible de valider les présences : aucun participant présent.'
            ], 400);
        }

        // Mettre à jour la validation des présences
        $meeting->update([
            'attendance_status' => 'validated',
            'attendance_validated_at' => now(),
            'attendance_validated_by' => Auth::id(),
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
            

            // Créer les éléments de paiement pour chaque participant présent
            foreach ($meeting->attendees()->where('attendance_status', 'present')->orWhere('attendance_status', 'replaced')->get() as $attendee) {
                $amount = 0;
                switch ($attendee->role) {
                    case 'president':
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

        return response()->json([
            'message' => 'Présences validées avec succès et liste de paiement générée.'
        ]);
    }

    /**
     * Rejeter une liste de présence (remet en état rejected avec commentaires)
     */
    public function rejectAttendance(Request $request, Meeting $meeting)
    {
        // Vérifier si l'utilisateur est un sous-préfet ou admin
        if (!Auth::user()->hasRole(['president', 'president', 'admin', 'Admin'])) {
            return response()->json([
                'message' => 'Vous n\'avez pas les droits pour rejeter la liste de présence de cette réunion.'
            ], 403);
        }

        // Valider les données de la requête
        $request->validate([
            'rejection_comments' => 'required|string|max:1000',
        ]);

        // Vérifier si les présences peuvent être rejetées
        if (!$meeting->isAttendanceValidated()) {
            return response()->json([
                'message' => 'Les présences ne sont pas validées.'
            ], 400);
        }

        // Mettre à jour la validation des présences (remet en état rejected)
        $meeting->update([
            'attendance_status' => 'rejected',
            'attendance_validated_at' => null,
            'attendance_validated_by' => null,
            'attendance_rejection_comments' => $request->rejection_comments,
            'attendance_rejected_at' => now(),
            'attendance_rejected_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Liste de présence rejetée avec succès. Le secrétaire peut maintenant la modifier et la resoumettre.'
        ]);
    }

    /**
     * Soumettre la liste de présence (sans finaliser la réunion)
     */
    public function submitAttendance(Meeting $meeting)
    {
        // Vérifier si l'utilisateur est un secrétaire ou admin
        if (!Auth::user()->hasRole(['secretaire', 'Secrétaire', 'admin', 'Admin'])) {
            return response()->json([
                'message' => 'Vous n\'avez pas les droits pour soumettre la liste de présence.'
            ], 403);
        }

        // Vérifier si les présences peuvent être soumises
        if ($meeting->attendance_status !== 'draft') {
            return response()->json([
                'message' => 'La liste de présence a déjà été soumise ou validée.'
            ], 400);
        }

        // Vérifier qu'il y a des participants présents
        $presentAttendees = $meeting->attendees()->where('is_present', true)->count();
        if ($presentAttendees === 0) {
            return response()->json([
                'message' => 'Impossible de soumettre la liste de présence : aucun participant présent.'
            ], 400);
        }

        // Marquer les attendees sans statut explicite comme absents
        $meeting->attendees()
            ->whereNull('attendance_status')
            ->update(['attendance_status' => 'absent', 'is_present' => false]);

        // Soumettre la liste de présence
        $meeting->update([
            'attendance_status' => 'submitted',
            'attendance_submitted_at' => now(),
            'attendance_submitted_by' => Auth::id(),
        ]);

        // Envoyer les notifications aux préfets
        $notificationService = app(\App\Services\AttendanceValidationNotificationService::class);
        $notificationsSent = $notificationService->sendValidationNotifications($meeting);

        $message = 'Liste de présence soumise avec succès.';
        if ($notificationsSent) {
            $message .= ' Les préfets ont été notifiés par email.';
        }

        return response()->json([
            'message' => $message
        ]);
    }

    /**
     * Annuler la soumission de la liste de présence
     */
    public function cancelAttendanceSubmission(Meeting $meeting)
    {
        // Vérifier si l'utilisateur est un secrétaire ou admin
        if (!Auth::user()->hasRole(['secretaire', 'Secrétaire', 'admin', 'Admin'])) {
            return response()->json([
                'message' => 'Vous n\'avez pas les droits pour annuler la soumission de la liste de présence.'
            ], 403);
        }

        // Vérifier si les présences peuvent être annulées
        if ($meeting->attendance_status !== 'submitted') {
            return response()->json([
                'message' => 'La liste de présence n\'est pas soumise.'
            ], 400);
        }

        // Annuler la soumission
        $meeting->update([
            'attendance_status' => 'draft',
            'attendance_submitted_at' => null,
            'attendance_submitted_by' => null,
        ]);

        return response()->json([
            'message' => 'Soumission de la liste de présence annulée avec succès.'
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

    /**
     * Afficher le formulaire d'éclatement d'une réunion
     */
    public function showSplitForm(Meeting $meeting)
    {
        // Vérifier si la réunion peut être éclatée
        if (!$meeting->canBeSplit()) {
            return redirect()->route('meetings.show', $meeting)
                ->with('error', 'Cette réunion ne peut pas être éclatée.');
        }
       
        // Vérifier s'il reste des villages disponibles
        $splitService = app(MeetingSplitService::class);
        $availableVillages = $splitService->getAvailableVillages($meeting);
        
        if (empty($availableVillages)) {
            return redirect()->route('meetings.show', $meeting)
                ->with('error', 'Tous les villages ont déjà été assignés à des sous-réunions. Aucun éclatement supplémentaire possible.');
        }

        // Charger les données nécessaires
        $meeting->load(['localCommittee.locality', 'attendees.locality']);

        return Inertia::render('Meetings/SplitMeeting', [
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'scheduled_date' => $meeting->scheduled_date,
                'location' => $meeting->location,
                'description' => $meeting->description,
                'target_enrollments' => $meeting->target_enrollments,
                'actual_enrollments' => $meeting->actual_enrollments,
                'status' => $meeting->status,
                'local_committee' => [
                    'id' => $meeting->localCommittee->id,
                    'name' => $meeting->localCommittee->name,
                    'locality' => [
                        'id' => $meeting->localCommittee->locality->id,
                        'name' => $meeting->localCommittee->locality->name,
                    ]
                ]
            ]
        ]);
    }

    /**
     * Éclater une réunion en sous-réunions
     */
    public function splitMeeting(Request $request, Meeting $meeting)
    {
        // Vérifier si la réunion peut être éclatée
        if (!$meeting->canBeSplit()) {
            return redirect()->route('meetings.show', $meeting)
                ->with('error', 'Cette réunion ne peut pas être éclatée.');
        }

        // Valider les données
        $validated = $request->validate([
            'sub_regions' => 'required|array|min:1',
            'sub_regions.*.id' => 'required|exists:localite,id',
            'sub_regions.*.name' => 'required|string',
            'sub_regions.*.villages' => 'required|array|min:1',
            'sub_regions.*.villages.*.id' => 'required|exists:localite,id',
            'sub_regions.*.location' => 'nullable|string',
        ]);

        try {
            $splitService = app(MeetingSplitService::class);
            $subMeetings = $splitService->splitMeeting($meeting, $validated['sub_regions']);

            return redirect()->route('meetings.show', $meeting)
                ->with('success', 'Réunion éclatée avec succès en ' . count($subMeetings) . ' sous-réunions.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'éclatement de la réunion: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function deleteSubMeeting(Meeting $meeting, Meeting $subMeeting)
    {
        // Vérifier que la sous-réunion appartient bien à la réunion parent
        if ($subMeeting->parent_meeting_id !== $meeting->id) {
            return back()->withErrors(['error' => 'Cette sous-réunion n\'appartient pas à cette réunion parent']);
        }
        
        // Vérifier les permissions (seuls les secrétaires peuvent supprimer)
        if (!auth()->user()->hasRole(['secretaire', 'Secrétaire'])) {
            return back()->withErrors(['error' => 'Vous n\'avez pas les permissions pour supprimer cette sous-réunion']);
        }
        
        // Vérifier que la sous-réunion n'est pas encore terminée
        if (in_array($subMeeting->status, ['completed', 'validated'])) {
            return back()->withErrors(['error' => 'Impossible de supprimer une sous-réunion déjà terminée']);
        }
        
        try {
            // Supprimer les participants de la sous-réunion
            $subMeeting->attendees()->delete();
            
            // Supprimer la sous-réunion
            $subMeeting->delete();
            
            return redirect()->route('meetings.show', $meeting)
                ->with('success', 'Sous-réunion supprimée avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de la suppression de la sous-réunion: ' . $e->getMessage()]);
        }
    }

    /**
     * Supprimer une réunion
     */
    public function destroy(Meeting $meeting)
    {
        // Vérifier les permissions (seuls les secrétaires et admins peuvent supprimer)
        if (!auth()->user()->hasRole(['secretaire', 'Secrétaire', 'admin', 'Admin'])) {
            return back()->withErrors(['error' => 'Vous n\'avez pas les permissions pour supprimer cette réunion']);
        }
        
        // Vérifier que la réunion n'est pas encore terminée
        if (in_array($meeting->status, ['completed', 'validated'])) {
            return back()->withErrors(['error' => 'Impossible de supprimer une réunion déjà terminée']);
        }
        
        // Vérifier qu'il n'y a pas de sous-réunions
        if ($meeting->subMeetings()->count() > 0) {
            return back()->withErrors(['error' => 'Impossible de supprimer une réunion qui a des sous-réunions. Supprimez d\'abord les sous-réunions.']);
        }
        
        try {
            // Supprimer les participants de la réunion
            $meeting->attendees()->delete();
            
            // Supprimer les pièces jointes
            $meeting->attachments()->delete();
            
            // Supprimer les comptes rendus
            $meeting->minutes()->delete();
            
            // Supprimer les commentaires
            $meeting->comments()->delete();
            
            // Supprimer la réunion
            $meeting->delete();
            
            return redirect()->route('meetings.index')
                ->with('success', 'Réunion supprimée avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de la suppression de la réunion: ' . $e->getMessage()]);
        }
    }
} 