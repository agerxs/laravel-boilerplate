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

        // L'administrateur et le super admin ont toujours accès
        if ($user->hasRole(['admin', 'Admin', 'super_admin'])) {
            return $next($request);
        }

        // Vérifier si l'utilisateur a un rôle autorisé
        if (!$user->hasRole(['president', 'sousprefet','President', 'secretaire', 'Secrétaire', 'tresorier', 'Tresorier'])) {
            return response()->view('errors.unauthorized', [
                'message' => 'Vous n\'avez pas accès au dashboard.'
            ], 403);
        }

        // Vérifier si l'utilisateur a une localité assignée (sauf pour l'admin et super admin)
        if (!$user->locality_id && !$user->hasRole(['admin', 'Admin', 'super_admin'])) {
            return response()->view('errors.unauthorized', [
                'message' => 'Votre compte n\'est pas correctement configuré. Veuillez contacter l\'administrateur.'
            ], 403);
        }

        return $next($request);
    }
} 