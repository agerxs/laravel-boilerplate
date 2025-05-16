<?php

namespace App\Http\Controllers;

use App\Models\LocalCommittee;
use App\Models\LocalCommitteeMember;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\AdministrativeDataService;
use Illuminate\Validation\Rule;
use App\Models\Locality;
use App\Models\Village;
use App\Models\Representative;
use App\Models\LocalCommitteeProgress;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class LocalCommitteeController extends Controller
{
    protected $administrativeService;

    public function __construct(AdministrativeDataService $administrativeService)
    {
        $this->administrativeService = $administrativeService;
    }

    public function index(Request $request)
    {
        $query = LocalCommittee::with(['locality', 'members.user']);
        
        $user = auth()->user();
        
        // Filtrer par localité si l'utilisateur est un préfet ou un secrétaire
        if ($user->hasRole(['prefet', 'Prefet', 'sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire'])) {
            if ($user->hasRole(['prefet', 'Prefet'])) {
                // Pour les préfets, montrer les comités de leur département et des sous-préfectures associées
                $query->whereHas('locality', function ($q) use ($user) {
                    $q->where('id', $user->locality_id)
                      ->orWhere('parent_id', $user->locality_id);
                });
            } else {
                // Pour les autres (sous-préfets et secrétaires), montrer uniquement les comités de leur localité
                $query->where('locality_id', $user->locality_id);
            }
        }
        
        // Appliquer des filtres si nécessaire
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('locality', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $committees = $query->paginate(10)->withQueryString();
        
        return Inertia::render('LocalCommittees/Index', [
            'committees' => $committees,
            'auth.user' => $user,
            'filters' => [
                'search' => $request->input('search', '')
            ]
        ]);
    }

    public function show($id)
    {
            $committee = LocalCommittee::with(['locality.children.representatives'])->findOrFail($id);
        return Inertia::render('LocalCommittees/Show', [
            'committee' => $committee,
        ]);
    }

    public function create()
    {
        // Récupérer les utilisateurs avec le rôle "Sous-préfet"
        $sousPrefets = User::role('Sous-prefet')
            ->with('locality')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'whatsapp' => $user->whatsapp,
                    'address' => $user->address,
                    'locality_name' => $user->locality?->name,
                ];
            });

        // Récupérer les utilisateurs avec le rôle "Secrétaire"
        $secretaires = User::role('Secrétaire')
            ->with('locality')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'whatsapp' => $user->whatsapp,
                    'address' => $user->address,
                    'locality_name' => $user->locality?->name,
                ];
            });

        // Récupérer la hiérarchie des localités
        $localities = $this->administrativeService->getLocalityHierarchy();
        
        // Passer les données à la vue Inertia
        return Inertia::render('LocalCommittees/Create', [
            'sousPrefets' => $sousPrefets,
            'secretaires' => $secretaires,
            'localities' => $localities,
        ]);
    }

    public function store(Request $request)
    {
        // Valider les données
        $request->validate([
            'name' => 'required|string|max:255',
            'locality_id' => 'required',
            'status' => 'required|string|in:active,inactive',
            'members' => 'required|json',
            'villages' => 'required|json',
        ]);
        
        // Créer le comité local
        $committee = new LocalCommittee();
        $committee->name = $request->name;
        $committee->locality_id = $request->locality_id;
        $committee->status = $request->status;
        
        // Vérifier si les colonnes existent avant d'assigner les valeurs
        if (Schema::hasColumn('local_committees', 'installation_date')) {
            $committee->installation_date = $request->installation_date;
        }
        
        if (Schema::hasColumn('local_committees', 'installation_location')) {
            $committee->installation_location = $request->installation_location;
        }
        
        // Gérer les fichiers
        if ($request->hasFile('decree_file') && Schema::hasColumn('local_committees', 'decree_file')) {
            $path = $request->file('decree_file')->store('committee_files', 'public');
            $committee->decree_file = $path;
        }
        
        if ($request->hasFile('installation_minutes_file') && Schema::hasColumn('local_committees', 'installation_minutes_file')) {
            $path = $request->file('installation_minutes_file')->store('committee_files', 'public');
            $committee->installation_minutes_file = $path;
        }
        
        if ($request->hasFile('attendance_list_file') && Schema::hasColumn('local_committees', 'attendance_list_file')) {
            $path = $request->file('attendance_list_file')->store('committee_files', 'public');
            $committee->attendance_list_file = $path;
        }
        
        $committee->save();
        
        // Ajouter les membres
        $members = json_decode($request->members, true);
        foreach ($members as $memberData) {
            $member = new LocalCommitteeMember();
            $member->local_committee_id = $committee->id;
            $member->user_id = $memberData['user_id'] ?? null;
            
            // Vérifier si la colonne is_user existe
            if (Schema::hasColumn('local_committee_members', 'is_user')) {
                $member->is_user = $memberData['is_user'] ?? false;
            }
            
            $member->first_name = $memberData['first_name'] ?? '';
            $member->last_name = $memberData['last_name'] ?? '';
            $member->phone = $memberData['phone'] ?? '';
            $member->role = $memberData['role'] ?? '';
            $member->status = $memberData['status'] ?? 'active';
            $member->save();
        }
        
        // Ajouter les villages et leurs représentants
        $villages = json_decode($request->villages, true);
        foreach ($villages as $villageData) {
            // Récupérer la localité qui est un village (type 7)
            $village = Locality::where('id', $villageData['id'])
                              ->where('locality_type_id', 7)
                              ->first();
            
            if ($village) {
                
                
                // Ajouter les représentants du village
                if (isset($villageData['representatives'])) {
                    foreach ($villageData['representatives'] as $repData) {
                        $representative = new Representative();
                        $representative->locality_id = $village->id;
                        $representative->local_committee_id = $committee->id;
                        $representative->first_name = $repData['first_name'];
                        $representative->last_name = $repData['last_name'];
                        $representative->phone = $repData['phone'];
                        $representative->role = $repData['role'];
                        $representative->save();
                    }
                }
            }
        }
        
        return response()->json([
            'message' => 'Comité local créé avec succès',
            'id' => $committee->id
        ]);
    }

    public function edit($id)
    {
        $committee = LocalCommittee::with(['locality.children.representatives', 'members.user'])->findOrFail($id);
        $localities = $this->administrativeService->getLocalityHierarchy();
       
        $users = User::all();
        $sousPrefets = User::role('Sous-prefet')
            ->with('locality')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'whatsapp' => $user->whatsapp,
                    'address' => $user->address,
                    'locality_name' => $user->locality?->name,
                ];
            });

        // Récupérer les utilisateurs avec le rôle "Secrétaire"
        $secretaires = User::role('Secrétaire')
            ->with('locality')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'whatsapp' => $user->whatsapp,
                    'address' => $user->address,
                    'locality_name' => $user->locality?->name,
                ];
            });

        return Inertia::render('LocalCommittees/Form', [
            'committee' => $committee,
            'localities' => $localities,
            'users' => $users,  
            'sousPrefets' => $sousPrefets,
            'secretaires' => $secretaires,
        ]);
    }

    public function update(Request $request, LocalCommittee $localCommittee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'locality_id' => 'required|exists:localite,id',
            'status' => 'required|in:active,inactive,pending',
            'members' => 'required|array|min:1',
            'members.*.is_user' => 'required|boolean',
            'members.*.role' => ['required', 'string', Rule::in(['secretary', 'president'])],
            'members.*.status' => 'required|in:active,inactive',
            // Validation pour les membres utilisateurs
            'members.*.user_id' => 'required_if:members.*.is_user,true|exists:users,id|nullable',
            // Validation pour les membres non-utilisateurs
            'members.*.first_name' => 'required_if:members.*.is_user,false|string|max:255|nullable',
            'members.*.last_name' => 'required_if:members.*.is_user,false|string|max:255|nullable',
            'members.*.phone' => 'nullable|string|max:20',
            'villages' => 'required|array',
            'villages.*.id' => 'required|exists:localite,id',
            'villages.*.representatives' => 'required|array',
            'villages.*.representatives.*.first_name' => 'required|string|max:255',
            'villages.*.representatives.*.last_name' => 'required|string|max:255',
            'villages.*.representatives.*.phone' => 'nullable|string|max:20',
            'villages.*.representatives.*.role' => 'required|string|max:255',
        ]);

        $localCommittee->update([
            'name' => $validated['name'],
            'locality_id' => $validated['locality_id'],
            'status' => $validated['status']
        ]);

        // Supprimer tous les membres actuels
        $localCommittee->members()->delete();

        // Ajouter les nouveaux membres
        
        foreach ($validated['members'] as $memberData) {
            $member = [
                'role' => $memberData['role'],
                'status' => $memberData['status']
            ];

            if ($memberData['is_user']) {
                $member['user_id'] = $memberData['user_id'];
            } else {
              
                $member['first_name'] = $memberData['first_name'];
                $member['last_name'] = $memberData['last_name'];
                $member['phone'] = $memberData['phone'] ?? null;
            }
           
           $localCommittee->members()->create($member);
        }

        foreach ($validated['villages'] as $villageData) {
            $village = Locality::find($villageData['id']);
            $village->representatives()->delete();
            foreach ($villageData['representatives'] as $repData) {
                $result=$village->representatives()->create($repData);
            }
        }

        return redirect()->route('local-committees.index')->with('success', 'Comité local créé avec succès');

    }

    public function destroy(LocalCommittee $localCommittee)
    {
        $localCommittee->delete();

        return redirect()->route('local-committees.index')
            ->with('success', 'Comité local supprimé avec succès');
    }

    public function showVillageRepresentatives($committeeId)
    {
        $committee = LocalCommittee::with('members.user')->findOrFail($committeeId);
        
        // Récupérer les villages de la localité du comité
        $villages = Locality::where('parent_id', $committee->locality_id)
            ->where('locality_type_id', 6) // Type village
            ->get();
            

        // Récupérer les représentants pour chaque village
        $representativesByVillage = [];
        foreach ($villages as $village) {
            $representatives = Representative::where('locality_id', $village->id)
              
                ->get();
            if ($representatives->isNotEmpty()) {
                $representativesByVillage[$village->name] = $representatives;
            }
        }

        return response()->json($representativesByVillage);
    }

    public function saveVillages(Request $request, $committeeId)
    {
        $validated = $request->validate([
            'villages' => 'required|array',
            'villages.*.id' => [
                'required',
                Rule::exists('localite', 'id')->where(function ($query) {
                    $query->where('locality_type_id', 7);
                }),
            ],
            'villages.*.representatives' => 'required|array',
            'villages.*.representatives.*.first_name' => 'nullable|string|max:255',
            'villages.*.representatives.*.last_name' => 'nullable|string|max:255',
            'villages.*.representatives.*.phone' => 'nullable|string|max:20',
            'villages.*.representatives.*.role' => 'nullable|string|max:255',
        ]);

        foreach ($validated['villages'] as $villageData) {
            $village = Locality::where('id', $villageData['id'])->where('locality_type_id', 7)->first();
            if ($village) {
                foreach ($villageData['representatives'] as $repData) {
                    // Vérifiez que les champs requis sont remplis
                    if (!empty($repData['first_name']) && !empty($repData['last_name']) && !empty($repData['role'])) {
                        $village->representatives()->create($repData);
                    }
                }
            }
        }

        return redirect()->route('local-committees.index')->with('success', 'Villages et représentants ajoutés avec succès.');
    }

    public function saveProgress(Request $request)
    {
        // Valider les données de base
        $request->validate([
            'name' => 'nullable|string|max:255',
            'locality_id' => 'nullable',
            'status' => 'nullable|string',
            'active_step' => 'nullable|integer',
        ]);

        // Récupérer l'utilisateur connecté
        $user = auth()->user();
        
        // Créer ou mettre à jour l'enregistrement de progression
        $progressData = [
            'name' => $request->name,
            'locality_id' => $request->locality_id,
            'status' => $request->status,
            'form_data' => json_encode([
                'members' => $request->has('members') ? json_decode($request->members) : [],
                'villages' => $request->has('villages') ? json_decode($request->villages) : [],
                'installation_date' => $request->installation_date,
                'installation_location' => $request->installation_location,
            ]),
            'user_id' => $user->id,
            'last_active_step' => $request->active_step ?? 0,
        ];

        // Gérer les fichiers
        $files = ['decree_file', 'installation_minutes_file', 'attendance_list_file'];
        $fileData = [];
        
        foreach ($files as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('temp_files/' . $user->id, $fileName, 'public');
                $fileData[$fileKey] = $filePath;
            }
        }
        
        if (!empty($fileData)) {
            $progressData['files'] = json_encode($fileData);
        }

        // Créer ou mettre à jour l'enregistrement
        $progress = LocalCommitteeProgress::updateOrCreate(
            ['user_id' => $user->id],
            $progressData
        );

        return response()->json(['message' => 'Progression sauvegardée avec succès.']);
    }

    /**
     * Récupère les brouillons de comités locaux de l'utilisateur connecté
     */
    public function getDrafts()
    {
        $user = auth()->user();
        $draft = LocalCommitteeProgress::where('user_id', $user->id)->first();
        
        if (!$draft) {
            return response()->json(['draft' => null]);
        }
        
        // Préparer les données du brouillon pour le frontend
        $draftData = [
            'id' => $draft->id,
            'name' => $draft->name,
            'locality_id' => $draft->locality_id,
            'status' => $draft->status,
            'last_active_step' => $draft->last_active_step,
            'created_at' => $draft->created_at,
            'updated_at' => $draft->updated_at,
            'form_data' => $draft->form_data,
            'files' => $draft->files
        ];
        
        return response()->json(['draft' => $draftData]);
    }

    /**
     * Charge un brouillon spécifique pour continuer l'édition
     */
    public function loadDraft($id)
    {
        $user = auth()->user();
        $draft = LocalCommitteeProgress::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Assurez-vous que les données sont correctement formatées
        $draftData = [
            'id' => $draft->id,
            'name' => $draft->name,
            'locality_id' => $draft->locality_id,
            'status' => $draft->status,
            'last_active_step' => $draft->last_active_step,
            'created_at' => $draft->created_at,
            'updated_at' => $draft->updated_at,
            'form_data' => $draft->form_data, // Assurez-vous que ceci est déjà décodé grâce au cast dans le modèle
            'files' => $draft->files // Assurez-vous que ceci est déjà décodé grâce au cast dans le modèle
        ];
        
        // Récupérer les utilisateurs avec le rôle "Sous-préfet"
        $sousPrefets = User::role('Sous-prefet')
            ->with('locality')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'whatsapp' => $user->whatsapp,
                    'address' => $user->address,
                    'locality_name' => $user->locality?->name,
                ];
            });

        // Récupérer les utilisateurs avec le rôle "Secrétaire"
        $secretaires = User::role('Secrétaire')
            ->with('locality')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'whatsapp' => $user->whatsapp,
                    'address' => $user->address,
                    'locality_name' => $user->locality?->name,
                ];
            });

        // Récupérer la hiérarchie des localités
        $localities = $this->administrativeService->getLocalityHierarchy();
        
        return Inertia::render('LocalCommittees/Create', [
            'sousPrefets' => $sousPrefets,
            'secretaires' => $secretaires,
            'localities' => $localities,
            'draft' => $draftData // Utilisez les données formatées
        ]);
    }

    /**
     * Supprime un brouillon
     */
    public function deleteDraft($id)
    {
        $user = auth()->user();
        $draft = LocalCommitteeProgress::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Supprimer les fichiers associés
        if (!empty($draft->files)) {
            foreach ($draft->files as $filePath) {
                Storage::disk('public')->delete($filePath);
            }
        }
        
        $draft->delete();
        
        return response()->json(['message' => 'Brouillon supprimé avec succès']);
    }

    public function getVillages($id)
    {
        $committee = LocalCommittee::findOrFail($id);
        
        // Récupérer les villages associés au comité local
        $villages = $committee->villages()->with('representatives')->get()->map(function ($village) {
            return [
                'id' => $village->id,
                'name' => $village->name,
                'representatives' => $village->representatives->map(function ($rep) {
                    return [
                        'id' => $rep->id,
                        'first_name' => $rep->first_name,
                        'last_name' => $rep->last_name,
                        'phone' => $rep->phone,
                        'role' => $rep->role
                    ];
                })
            ];
        });
        
        return response()->json([
            'committee' => [
                'id' => $committee->id,
                'name' => $committee->name
            ],
            'villages' => $villages
        ]);
    }

} 