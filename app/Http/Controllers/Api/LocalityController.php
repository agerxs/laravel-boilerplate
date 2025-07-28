<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Locality;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Utils\Constants;


class LocalityController extends Controller
{
    public function index(): JsonResponse
    {
        $localities = Locality::with(['parent', 'children', 'type'])->get();
        return response()->json($localities);
    }

    public function show(Locality $locality): JsonResponse
    {
        $locality->load(['parent', 'children', 'type']);
        return response()->json($locality);
    }

    public function update(Request $request, Locality $locality): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type_id' => 'sometimes|required|exists:locality_types,id',
            'parent_id' => 'nullable|exists:localities,id',
            'code' => 'nullable|string|max:50|unique:localities,code,' . $locality->id,
            'description' => 'nullable|string'
        ]);

        $locality->update($validated);
        $locality->load(['parent', 'children', 'type']);
        
        return response()->json($locality);
    }

    public function children(Locality $locality): JsonResponse
    {
        $children = $locality->children()->with(['type'])->get();
        return response()->json($children);
    }

    public function villages(Request $request): JsonResponse
    {
        $user = $request->user();
        // Récupérer les comités locaux de l'utilisateur
        $committees = $user->localCommittees()->with('locality')->get();
        $localityIds = $committees->pluck('locality.id')->filter()->unique();
        $villages = collect();
        foreach ($localityIds as $localityId) {
            $children = Locality::where('parent_id', $localityId)
                ->whereHas('type', function($q) { $q->where('name', 'village'); })
                ->get();
            $villages = $villages->merge($children);
        }
        // On retourne la liste unique des villages (par id)
        $villages = $villages->unique('id')->values();
        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste des villages récupérée avec succès', [
            'villages' => $villages,
        ]);
    }
} 