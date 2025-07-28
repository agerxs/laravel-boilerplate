<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Representative;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Utils\Constants;
use App\Services\AdministrativeDataService;


class RepresentativeController extends Controller
{
    protected $administrativeService;

    public function __construct(AdministrativeDataService $administrativeService)
    {
        $this->administrativeService = $administrativeService;
    }

    /**
     * Retourne tous les représentants
     */
    public function index()
    {
        $representatives = Representative::with(['localCommittee', 'locality'])->get();
        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste des représentants récupérée avec succès', [
            'representatives' => $representatives
        ]);
    }

    /**
     * Crée un nouveau représentant
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'localite_id' => 'required|exists:localite,id',
            'localCommitteeId' => 'required|exists:local_committees,id',
            'role' => 'required|string|max:255'
        ]);
        $validated['local_committee_id'] = $validated['localCommitteeId'];
        $validated['locality_id'] = $validated['localite_id'];
        unset($validated['localCommitteeId']);
        $representative = Representative::create($validated);
        return $this->format(Constants::JSON_STATUS_SUCCESS, 201, 'Représentant créé avec succès', [
            'representative' => $representative
        ]);
    }

    /**
     * Retourne un représentant spécifique
     */
    public function show(Representative $representative)
    {
        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Représentant récupéré avec succès', [
            'representative' => $representative->load(['localCommittee', 'locality'])
        ]);
    }

    /**
     * Met à jour un représentant
     */
    public function update(Request $request, Representative $representative)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'locality_id' => 'sometimes|required|exists:localities,id',
            'localCommitteeId' => 'sometimes|required|exists:local_committees,id',
            'role' => 'sometimes|required|string|max:255'
        ]);

        $representative->update($validated);
        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Représentant mis à jour avec succès', [
            'representative' => $representative->fresh(['localCommittee', 'locality'])
        ]);
    }

    /**
     * Supprime un représentant
     */
    public function destroy(Representative $representative)
    {
        $representative->delete();
        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Représentant supprimé avec succès');
    }

    /**
     * Retourne tous les représentants des comités locaux de l'utilisateur connecté (API)
     */
    public function myRepresentatives()
    {
        info("here");
        $user = auth()->user();
        info($user);
        $committeeIds = $user->localCommittees()->pluck('local_committees.id')->toArray();
        info($committeeIds);

        // Récupérer toutes les localités des comités locaux
        $committeeLocalityIds = \App\Models\LocalCommittee::whereIn('id', $committeeIds)
            ->pluck('locality_id')
            ->toArray();

        // Récupérer tous les villages enfants de ces localités
        $villageIds = \App\Models\Locality::whereIn('parent_id', $committeeLocalityIds)
            ->pluck('id')
            ->toArray();

        // Récupérer les représentants de ces villages
        $representatives = \App\Models\Representative::whereIn('locality_id', $villageIds)
            ->with(['localCommittee', 'locality'])
            ->get();

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste des representants récupérée avec succès', [
            'representatives' => $representatives
        ]);
    }
} 