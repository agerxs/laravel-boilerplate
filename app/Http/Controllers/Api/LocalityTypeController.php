<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocalityType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocalityTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = LocalityType::withCount('localities')->get();
        return response()->json($types);
    }

    public function show(LocalityType $type): JsonResponse
    {
        $type->loadCount('localities');
        return response()->json($type);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:locality_types',
            'description' => 'nullable|string',
            'level' => 'required|integer|min:0',
            'can_have_committee' => 'boolean'
        ]);

        $type = LocalityType::create($validated);
        return response()->json($type, 201);
    }

    public function update(Request $request, LocalityType $type): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:locality_types,name,' . $type->id,
            'description' => 'nullable|string',
            'level' => 'sometimes|required|integer|min:0',
            'can_have_committee' => 'boolean'
        ]);

        $type->update($validated);
        return response()->json($type);
    }

    public function destroy(LocalityType $type): JsonResponse
    {
        if ($type->localities()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un type qui est utilisé par des localités'
            ], 422);
        }

        $type->delete();
        return response()->json(null, 204);
    }

    public function localities(LocalityType $type): JsonResponse
    {
        $localities = $type->localities()->with(['parent', 'children'])->get();
        return response()->json($localities);
    }
} 