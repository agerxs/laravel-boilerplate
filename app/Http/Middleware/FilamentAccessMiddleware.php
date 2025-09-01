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
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Vérifier si l'utilisateur peut accéder au dashboard
        if (!$user->canViewDashboard()) {
            abort(403, 'Accès non autorisé au dashboard Filament.');
        }

        return $next($request);
    }
}
