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

    public function show(LocalCommittee $localCommittee)
    {
        $localCommittee->load(['locality', 'members.user' => function($query) {
            $query->select('id', 'name', 'phone');
        }]);
        
        return Inertia::render('LocalCommittees/Show', [
            'committee' => $localCommittee
        ]);
    }

    public function create()
    {
        return Inertia::render('LocalCommittees/Create', [
            'users' => User::role('Secretaire')
                ->with('locality')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'locality_name' => $user->locality?->name,
                        'phone' => $user->phone
                    ];
                }),
            'localities' => $this->administrativeService->getLocalityHierarchy()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'locality_id' => 'required|exists:localities,id',
            'status' => 'required|in:active,inactive,pending',
            'members' => 'required|array|min:1',
            'members.*.is_user' => 'required|boolean',
            'members.*.role' => ['required', 'string', Rule::in(['secretary', 'member'])],
            'members.*.status' => 'required|in:active,inactive',
            // Validation pour les membres utilisateurs
            'members.*.user_id' => 'required_if:members.*.is_user,true|exists:users,id|nullable',
            // Validation pour les membres non-utilisateurs
            'members.*.first_name' => 'required_if:members.*.is_user,false|string|max:255|nullable',
            'members.*.last_name' => 'required_if:members.*.is_user,false|string|max:255|nullable',
            'members.*.phone' => 'nullable|string|max:20'
        ]);

        $committee = LocalCommittee::create([
            'name' => $validated['name'],
            'locality_id' => $validated['locality_id'],
            'status' => $validated['status']
        ]);

        // Créer les membres
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

        return redirect()
            ->route('local-committees.show', $committee)
            ->with('success', 'Comité local créé avec succès');
    }

    public function edit(LocalCommittee $localCommittee)
    {
        $localCommittee->load(['locality', 'members.user']);

        return Inertia::render('LocalCommittees/Edit', [
            'committee' => $localCommittee,
            'users' => User::role('Secretaire')
                ->with('locality')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'locality_name' => $user->locality?->name,
                        'phone' => $user->phone
                    ];
                }),
            'localities' => $this->administrativeService->getLocalityHierarchy()
        ]);
    }

    public function update(Request $request, LocalCommittee $localCommittee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'locality_id' => 'required|exists:localities,id',
            'status' => 'required|in:active,inactive,pending',
            'members' => 'required|array|min:1',
            'members.*.is_user' => 'required|boolean',
            'members.*.role' => ['required', 'string', Rule::in(['secretary', 'member'])],
            'members.*.status' => 'required|in:active,inactive',
            // Validation pour les membres utilisateurs
            'members.*.user_id' => 'required_if:members.*.is_user,true|exists:users,id|nullable',
            // Validation pour les membres non-utilisateurs
            'members.*.first_name' => 'required_if:members.*.is_user,false|string|max:255|nullable',
            'members.*.last_name' => 'required_if:members.*.is_user,false|string|max:255|nullable',
            'members.*.phone' => 'nullable|string|max:20'
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

        return redirect()
            ->route('local-committees.show', $localCommittee)
            ->with('success', 'Comité local mis à jour avec succès');
    }

    public function destroy(LocalCommittee $localCommittee)
    {
        $localCommittee->delete();

        return redirect()->route('local-committees.index')
            ->with('success', 'Comité local supprimé avec succès');
    }
} 