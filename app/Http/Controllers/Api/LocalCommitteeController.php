<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocalCommittee;
use Illuminate\Http\Request;

class LocalCommitteeController extends Controller
{
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
        
        $committees = $query->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $committees->items(),
            'meta' => [
                'current_page' => $committees->currentPage(),
                'last_page' => $committees->lastPage(),
                'per_page' => $committees->perPage(),
                'total' => $committees->total(),
            ],
            'links' => [
                'first' => $committees->url(1),
                'last' => $committees->url($committees->lastPage()),
                'prev' => $committees->previousPageUrl(),
                'next' => $committees->nextPageUrl(),
            ],
        ]);
    }

    public function show(LocalCommittee $localCommittee)
    {
        $localCommittee->load(['locality', 'members.user']);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $localCommittee->id,
                'name' => $localCommittee->name,
                'locality' => $localCommittee->locality ? [
                    'id' => $localCommittee->locality->id,
                    'name' => $localCommittee->locality->name,
                ] : null,
                'members' => $localCommittee->members->map(function($member) {
                    return [
                        'id' => $member->id,
                        'user_id' => $member->user_id,
                        'first_name' => $member->first_name,
                        'last_name' => $member->last_name,
                        'phone' => $member->phone,
                        'role' => $member->role,
                        'status' => $member->status,
                        'user' => $member->user ? [
                            'id' => $member->user->id,
                            'name' => $member->user->name,
                            'email' => $member->user->email,
                        ] : null,
                    ];
                }),
                'created_at' => $localCommittee->created_at,
                'updated_at' => $localCommittee->updated_at,
            ],
        ]);
    }
} 