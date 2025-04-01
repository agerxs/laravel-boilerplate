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

    public function index()
    {
        $representatives = Representative::with(['locality', 'localCommittee'])->get();
        
        // Récupérer la sous-préfecture de l'utilisateur connecté
        $userSubPrefecture = auth()->user()->locality;
        
        // Récupérer les villages de la sous-préfecture
        $villages = Locality::where('locality_type_id', 6)
            ->where('parent_id', $userSubPrefecture->id)
            ->get();

        return Inertia::render('Representatives/Index', [
            'representatives' => $representatives,
            'villages' => $villages,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'locality_id' => 'required|exists:localite,id',
            'role' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        
        $representative = Representative::create($validated);

        return redirect()->back()->with('success', 'Représentant créé avec succès.');
    }

    public function update(Request $request, Representative $representative)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'locality_id' => 'required|exists:localite,id',
            'role' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $representative->update($validated);

        return redirect()->back()->with('success', 'Représentant mis à jour avec succès.');
    }

    public function destroy(Representative $representative)
    {
        $representative->delete();

        return redirect()->back()->with('success', 'Représentant supprimé avec succès.');
    }
} 