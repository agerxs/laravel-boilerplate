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

class LocalCommitteeController extends Controller
{
    protected $administrativeService;

    public function __construct(AdministrativeDataService $administrativeService)
    {
        $this->administrativeService = $administrativeService;
    }

    public function index(Request $request)
    {
        $query = LocalCommittee::query()
            ->with(['locality', 'members.user'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('locality', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('members.user', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        return Inertia::render('LocalCommittees/Index', [
            'committees' => $query->paginate(10),
            'filters' => [
                'search' => $request->get('search', '')
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'locality_id' => 'required|exists:localite,id',
            'status' => 'required|in:active,inactive,pending',
            'members' => 'required|array|min:1',
            'members.*.is_user' => 'required|boolean',
            'members.*.role' => ['required', 'string', Rule::in(['secretary', 'president'])],
            'members.*.status' => 'required|in:active,inactive',
            'members.*.user_id' => 'required_if:members.*.is_user,true|exists:users,id|nullable',
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

        $committee = LocalCommittee::create([
            'name' => $validated['name'],
            'locality_id' => $validated['locality_id'],
            'status' => $validated['status']
        ]);

        
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
           
           $committee->members()->create($member);
        }

        foreach ($validated['villages'] as $villageData) {
            $village = Locality::find($villageData['id']);
            foreach ($villageData['representatives'] as $repData) {
                $result=$village->representatives()->create($repData);
            }
        }

        return redirect()->route('local-committees.index')->with('success', 'Comité local créé avec succès');
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

        return Inertia::render('LocalCommittees/VillageRepresentatives', [
            'committee' => $committee,
            'permanentMembers' => $committee->members->whereIn('role', ['president', 'secretary']),
        ]);
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

} 