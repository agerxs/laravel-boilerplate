<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // L'administrateur a toujours accès
        if ($user->hasRole(['admin', 'Admin'])) {
            return $next($request);
        }

        // Vérifier si l'utilisateur a un rôle autorisé
        if (!$user->hasRole(['sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire', 'gestionnaire', 'Gestionnaire'])) {
            return response()->view('errors.unauthorized', [
                'message' => 'Vous n\'avez pas accès au dashboard.'
            ], 403);
        }

        // Vérifier si l'utilisateur a une localité assignée (sauf pour l'admin)
        if (!$user->locality_id) {
            return response()->view('errors.unauthorized', [
                'message' => 'Votre compte n\'est pas correctement configuré. Veuillez contacter l\'administrateur.'
            ], 403);
        }

        return $next($request);
    }
} 