<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Utils\Constants;


class AuthController extends Controller
{
    /**
     * Authentification de l'utilisateur
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        $user = User::where('email', $request->email)
            ->with(['roles', 'locality', 'localCommittees'])
            ->firstOrFail();
            
        $token = $user->createToken('auth-token')->plainTextToken;

        // Récupérer tous les comités locaux de la localité de l'utilisateur
        $localCommittees = $user->locality->localCommittees ?? collect();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'locality_id' => $user->locality_id,
                'local_committees' => $localCommittees->map(function($committee) {
                    return [
                        'id' => $committee->id,
                        'name' => $committee->name,
                        'locality_id' => $committee->locality_id,
                        'president_id' => $committee->president_id,
                        'installation_date' => $committee->installation_date,
                        'ano_validation_date' => $committee->ano_validation_date,
                        'fund_transmission_date' => $committee->fund_transmission_date,
                        'villages_count' => $committee->villages_count,
                        'population_rgph' => $committee->population_rgph,
                        'population_to_enroll' => $committee->population_to_enroll,
                        'status' => $committee->status,
                        'created_at' => $committee->created_at,
                        'updated_at' => $committee->updated_at
                    ];
                })->toArray()
            ]
        ]);
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')
            ]
        ], 201);
    }

    /**
     * Déconnexion de l'utilisateur
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Récupérer les informations de l'utilisateur connecté
     */
    public function user(Request $request)
    {
        $user = $request->user()->load(['roles', 'localCommitteeMembers.localCommittee']);
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'local_committee_members' => $user->localCommitteeMembers->map(function($member) {
                    return [
                        'id' => $member->id,
                        'local_committee_id' => $member->local_committee_id,
                        'user_id' => $member->user_id,
                        'first_name' => $member->first_name,
                        'last_name' => $member->last_name,
                        'phone' => $member->phone,
                        'role' => $member->role,
                        'status' => $member->status,
                        'local_committee' => $member->localCommittee ? [
                            'id' => $member->localCommittee->id,
                            'name' => $member->localCommittee->name,
                            'locality_id' => $member->localCommittee->locality_id,
                            'president_id' => $member->localCommittee->president_id,
                            'installation_date' => $member->localCommittee->installation_date,
                            'ano_validation_date' => $member->localCommittee->ano_validation_date,
                            'fund_transmission_date' => $member->localCommittee->fund_transmission_date,
                            'villages_count' => $member->localCommittee->villages_count,
                            'population_rgph' => $member->localCommittee->population_rgph,
                            'population_to_enroll' => $member->localCommittee->population_to_enroll,
                            'status' => $member->localCommittee->status,
                            'created_at' => $member->localCommittee->created_at,
                            'updated_at' => $member->localCommittee->updated_at
                        ] : null
                    ];
                })->toArray()
            ]
        ]);
    }
} 