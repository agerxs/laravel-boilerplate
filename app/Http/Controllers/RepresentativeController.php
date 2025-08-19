<?php

namespace App\Http\Controllers;

use App\Models\Representative;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Locality;
use App\Services\AdministrativeDataService;


class RepresentativeController extends Controller
{
    protected $administrativeService;

    public function __construct(AdministrativeDataService $administrativeService)
    {
        $this->administrativeService = $administrativeService;
    }

    public function index(Request $request)
    {
        // Optimisation de cette zone necessaire pour éviter les requêtes inutiles

        
        // Récuperation de l'utilisateur connecté et de ses comités locaux
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Recuparation de la localité de l'utilisateur
        $userLocality = $user->locality_id;

        // Recuperation des localitées filles
        $localities = Locality::where('parent_id', $userLocality)->pluck('id')->toArray();

        $query = Representative::with(['locality', 'localCommittee']);

        // Filtres
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('local_committee_id')) {
            $query->where('local_committee_id', $request->local_committee_id);
        }

        if ($request->filled('locality_id')) {
            $query->where('locality_id', $request->locality_id);
        } else if ($localities !== null) {
            $query->whereIn('locality_id', $localities);
        }

        $userSubPrefecture = $user->locality;
        
        $villages = Locality::where('locality_type_id', 6)
            ->where('parent_id', $userSubPrefecture->id)
            ->with('representatives')
            ->get();

        $committeeQuery = \App\Models\LocalCommittee::query();
        if ($user->hasRole(['president', 'President', 'secretaire', 'Secrétaire'])) {
            $committeeQuery->where('locality_id', $user->locality_id);
        }
        $localCommittees = $committeeQuery->get();

        return Inertia::render('Representatives/Index', [
            'representatives' => $query->paginate(15)->withQueryString(),
            'villages' => $villages,
            'localCommittees' => $localCommittees,
            'filters' => $request->only(['search', 'local_committee_id', 'locality_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'locality_id' => 'required|exists:localite,id',
            'local_committee_id' => 'required|exists:local_committees,id',
            'role' => 'required|string|max:255',
            'phone' => 'required|max:255',
        ]);
        
        $representative = Representative::create($validated);

        return redirect()->back()->with('success', 'Représentant créé avec succès.');
    }

    public function update(Request $request, Representative $representative)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'locality_id' => 'required|exists:localite,id',
            'local_committee_id' => 'required|exists:local_committees,id',
            'role' => 'required|string|max:255',
            'phone' => 'required|max:255',
        ]);

        $representative->update($validated);

        return redirect()->back()->with('success', 'Représentant mis à jour avec succès.');
    }

    public function destroy(Representative $representative)
    {
        $representative->delete();

        return redirect()->back()->with('success', 'Représentant supprimé avec succès.');
    }

    /**
     * Retourne tous les représentants des comités locaux de l'utilisateur connecté (API)
     */
    public function myRepresentatives()
    {
        $user = auth()->user();
        $committeeIds = $user->localCommittees()->pluck('local_committees.id')->toArray();

        $representatives = \App\Models\Representative::whereIn('local_committee_id', $committeeIds)
            ->with(['localCommittee', 'locality'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $representatives
        ]);
    }
} 