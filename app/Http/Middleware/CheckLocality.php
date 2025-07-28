<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckLocality
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Les gestionnaires ont accès à toutes les localités
        if (in_array('gestionnaire', $user->roles->pluck('name')->toArray()) || in_array('Gestionnaire', $user->roles->pluck('name')->toArray())) {
            return $next($request);
        }
        
        $meeting = $request->route('meeting');
        $localCommittee = $request->route('localCommittee');

        if (!$user->locality_id) {
            return redirect()->route('login')->with('error', 'Votre compte n\'est pas correctement configuré. Veuillez contacter l\'administrateur.');
        }

        if ($meeting && $meeting->localCommittee->locality_id !== $user->locality_id) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas accès à cette réunion.');
        }

        if ($localCommittee && $localCommittee->locality_id !== $user->locality_id) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas accès à ce comité local.');
        }

        return $next($request);
    }
} 