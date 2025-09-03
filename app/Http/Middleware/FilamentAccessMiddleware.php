<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class FilamentAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info("FilamentAccessMiddleware::handle() - Début du contrôle d'accès");
        
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            \Log::info("FilamentAccessMiddleware::handle() - Utilisateur non authentifié, redirection vers login");
            return redirect()->route('filament.admin.auth.login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        \Log::info("FilamentAccessMiddleware::handle() - Utilisateur authentifié: {$user->email}");

        // Vérifier si l'utilisateur peut accéder au dashboard
        $canView = $user->canViewDashboard();
        \Log::info("FilamentAccessMiddleware::handle() - canViewDashboard() result: " . ($canView ? 'true' : 'false'));
        
        if (!$canView) {
            \Log::warning("FilamentAccessMiddleware::handle() - Accès refusé pour {$user->email}");
            abort(403, 'Accès non autorisé au dashboard Filament.');
        }

        \Log::info("FilamentAccessMiddleware::handle() - Accès autorisé pour {$user->email}");
        return $next($request);
    }
}
