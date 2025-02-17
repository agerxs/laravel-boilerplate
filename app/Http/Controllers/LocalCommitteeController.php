<?php

namespace App\Http\Controllers;

use App\Models\LocalCommittee;
use App\Models\LocalCommitteeMember;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\AdministrativeDataService;
use Illuminate\Validation\Rule;

class LocalCommitteeController extends Controller
{
    public function index(Request $request)
    {
        $query = LocalCommittee::query()
            ->with('members')
            ->orderBy('created_at', 'desc');

        // Recherche
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('members', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $committees = $query->paginate($perPage);

        return Inertia::render('LocalCommittees/Index', [
            'committees' => $committees,
            'filters' => [
                'search' => $request->get('search', ''),
                'per_page' => $perPage
            ]
        ]);
    }

    public function create()
    {
        $administrativeService = new AdministrativeDataService();
        
        return Inertia::render('LocalCommittees/Create', [
            'users' => User::select('id', 'name', 'email')->orderBy('name')->get(),
            'localities' => $administrativeService->getAllLocalities(),
            'topLevelLocalities' => $administrativeService->getLocalitiesByParent(null)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'members' => 'required|array|min:1',
            'members.*.role' => ['required', 'string', Rule::in([
                'secretaire',
                'chef_village',
                'president_jeunes',
                'presidente_femmes'
            ])],
            'members.*.is_user' => 'required|boolean',
            // Validation conditionnelle pour les membres utilisateurs
            'members.*.user_id' => 'required_if:members.*.is_user,true|exists:users,id',
            // Validation pour les membres non-utilisateurs
            'members.*.first_name' => 'required_if:members.*.is_user,false',
            'members.*.last_name' => 'required_if:members.*.is_user,false',
            'members.*.phone' => 'required_if:members.*.is_user,false'
        ]);

        $committee = LocalCommittee::create([
            'name' => $request->name,
            'description' => $request->description,
            'city' => $request->city,
            'address' => $request->address
        ]);

        // Attacher les membres avec leurs rôles
        foreach ($request->members as $member) {
            $memberData = [
                'role' => $member['role']
            ];

            if ($member['is_user']) {
                // Pour les utilisateurs (secrétaire)
                $memberData['user_id'] = $member['user_id'];
            } else {
                // Pour les non-utilisateurs (autres rôles)
                $memberData['first_name'] = $member['first_name'];
                $memberData['last_name'] = $member['last_name'];
                $memberData['phone'] = $member['phone'];
            }

            $committee->members()->create($memberData);
        }

        return redirect()->route('local-committees.index')
            ->with('success', 'Comité local créé avec succès');
    }

    public function edit(LocalCommittee $localCommittee)
    {
        $localCommittee->load('members');

        return Inertia::render('LocalCommittees/Edit', [
            'committee' => $localCommittee,
            'users' => User::select('id', 'name', 'email')->get()
        ]);
    }

    public function update(Request $request, LocalCommittee $localCommittee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'members' => 'required|array|min:1',
            'members.*.role' => ['required', 'string', Rule::in([
                'secretaire',
                'chef_village',
                'president_jeunes',
                'presidente_femmes'
            ])],
            'members.*.is_user' => 'required|boolean',
            // Validation conditionnelle pour les membres utilisateurs
            'members.*.user_id' => 'required_if:members.*.is_user,true|exists:users,id',
            // Validation pour les membres non-utilisateurs
            'members.*.first_name' => 'required_if:members.*.is_user,false',
            'members.*.last_name' => 'required_if:members.*.is_user,false',
            'members.*.phone' => 'required_if:members.*.is_user,false'
        ]);

        $localCommittee->update([
            'name' => $request->name,
            'description' => $request->description,
            'city' => $request->city,
            'address' => $request->address
        ]);

        // Supprimer tous les membres actuels
        $localCommittee->members()->delete();

        // Ajouter les nouveaux membres
        foreach ($request->members as $member) {
            $memberData = [
                'role' => $member['role']
            ];

            if ($member['is_user']) {
                // Pour les utilisateurs (secrétaire)
                $memberData['user_id'] = $member['user_id'];
            } else {
                // Pour les non-utilisateurs (autres rôles)
                $memberData['first_name'] = $member['first_name'];
                $memberData['last_name'] = $member['last_name'];
                $memberData['phone'] = $member['phone'];
            }

            $localCommittee->members()->create($memberData);
        }

        return redirect()->route('local-committees.index')
            ->with('success', 'Comité local mis à jour avec succès');
    }

    public function destroy(LocalCommittee $localCommittee)
    {
        $localCommittee->delete();

        return redirect()->route('local-committees.index')
            ->with('success', 'Comité local supprimé avec succès');
    }

    public function show(LocalCommittee $localCommittee)
    {
        $localCommittee->load(['members', 'meetings' => function ($query) {
            $query->orderBy('start_datetime', 'desc');
        }]);
        
        return Inertia::render('LocalCommittees/Show', [
            'committee' => $localCommittee
        ]);
    }
} 