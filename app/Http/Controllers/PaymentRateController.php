<?php

namespace App\Http\Controllers;

use App\Models\PaymentRate;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class PaymentRateController extends Controller
{
    /**
     * Afficher la liste des taux de paiement
     */
    public function index(Request $request)
    {
        // Récupérer les utilisateurs avec leurs rôles et taux de paiement
        $query = User::with(['roles', 'paymentRates'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Prefet', 'Sous-prefet', 'Secrétaire']);
            });
        
        // Appliquer la recherche si elle existe
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filtrer par rôle si spécifié
        if ($request->has('role') && $request->input('role')) {
            $role = $request->input('role');
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }
        
        $users = $query->paginate(10)
            ->withQueryString()
            ->through(function ($user) {
                $paymentRates = [];
                
                foreach ($user->paymentRates as $rate) {
                    $paymentRates[$rate->role] = [
                        'id' => $rate->id,
                        'meeting_rate' => $rate->meeting_rate,
                        'is_active' => $rate->is_active,
                        'notes' => $rate->notes
                    ];
                }
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'payment_rates' => $paymentRates
                ];
            });
        
        // Récupérer les rôles pour le filtre
        $roles = Role::whereIn('name', ['Prefet', 'Sous-prefet', 'Secrétaire'])->get();
        
        return Inertia::render('PaymentRates/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', '')
            ]
        ]);
    }
    
    /**
     * Afficher le formulaire de création d'un taux de paiement
     */
    public function create()
    {
        $roles = [
            'prefet' => 'Préfet',
            'sous_prefet' => 'Sous-préfet',
            'secretaire' => 'Secrétaire',
            'representant' => 'Représentant'
        ];
        
        return Inertia::render('PaymentRates/Create', [
            'roles' => $roles
        ]);
    }
    
    /**
     * Enregistrer un nouveau taux de paiement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|string',
            'meeting_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        
        // Si ce taux est actif, désactiver les autres taux pour ce rôle
        if ($validated['is_active'] ?? true) {
            DB::transaction(function () use ($validated) {
                PaymentRate::where('role', $validated['role'])
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
                
                PaymentRate::create($validated);
            });
        } else {
            PaymentRate::create($validated);
        }
        
        return redirect()->route('payment-rates.index')
            ->with('success', 'Taux de paiement créé avec succès.');
    }
    
    /**
     * Afficher le formulaire d'édition d'un taux de paiement
     */
    public function edit(PaymentRate $paymentRate)
    {
        $roles = [
            'prefet' => 'Préfet',
            'sous_prefet' => 'Sous-préfet',
            'secretaire' => 'Secrétaire',
            'representant' => 'Représentant'
        ];
        
        return Inertia::render('PaymentRates/Edit', [
            'paymentRate' => $paymentRate,
            'roles' => $roles
        ]);
    }
    
    /**
     * Mettre à jour un taux de paiement
     */
    public function update(Request $request, PaymentRate $paymentRate)
    {
        $validated = $request->validate([
            'role' => 'required|string',
            'meeting_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        
        // Si ce taux est actif, désactiver les autres taux pour ce rôle
        if ($validated['is_active'] ?? false) {
            DB::transaction(function () use ($validated, $paymentRate) {
                PaymentRate::where('role', $validated['role'])
                    ->where('id', '!=', $paymentRate->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
                
                $paymentRate->update($validated);
            });
        } else {
            $paymentRate->update($validated);
        }
        
        return redirect()->route('payment-rates.index')
            ->with('success', 'Taux de paiement mis à jour avec succès.');
    }
    
    /**
     * Supprimer un taux de paiement
     */
    public function destroy(PaymentRate $paymentRate)
    {
        $paymentRate->delete();
        
        return redirect()->route('payment-rates.index')
            ->with('success', 'Taux de paiement supprimé avec succès.');
    }
    
    /**
     * Mettre à jour les taux de paiement en masse
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'rates' => 'required|array',
            'rates.*.id' => 'required|exists:payment_rates,id',
            'rates.*.meeting_rate' => 'required|numeric|min:0',
            'rates.*.is_active' => 'boolean'
        ]);
        
        DB::transaction(function () use ($validated) {
            foreach ($validated['rates'] as $rateData) {
                $rate = PaymentRate::find($rateData['id']);
                
                // Si ce taux est actif, désactiver les autres taux pour ce rôle
                if ($rateData['is_active'] ?? false) {
                    PaymentRate::where('role', $rate->role)
                        ->where('id', '!=', $rate->id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);
                }
                
                $rate->update([
                    'meeting_rate' => $rateData['meeting_rate'],
                    'is_active' => $rateData['is_active'] ?? false
                ]);
            }
        });
        
        return redirect()->route('payment-rates.index')
            ->with('success', 'Taux de paiement mis à jour avec succès.');
    }
} 