<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Locality;
use App\Models\LocalCommittee;
use App\Models\Meeting;

class CheckLocalityAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Les gestionnaires ont accès à toutes les localités
        if (in_array('tresorier', $user->roles->pluck('name')->toArray()) || in_array('Tresorier', $user->roles->pluck('name')->toArray())) {
            return $next($request);
        }
        
        if (in_array('president', $user->roles->pluck('name')->toArray()) || in_array('President', $user->roles->pluck('name')->toArray()) || 
            in_array('secretaire', $user->roles->pluck('name')->toArray()) || in_array('Secrétaire', $user->roles->pluck('name')->toArray())) {
            // Si l'utilisateur n'a pas de localité assignée, accès refusé
            if (!$user->locality_id) {
                abort(403, "Vous n'avez pas de localité assignée.");
            }
            
            // Récupérer la localité demandée - elle peut être dans différents paramètres selon la route
            $requestedLocalityId = null;
            $routeParams = $request->route()->parameters();
            
            try {
                // Vérifier les paramètres de route classiques
                if ($request->route('localCommittee')) {
                    $localCommittee = $request->route('localCommittee');
                    // Vérifier si $localCommittee est un objet ou un ID (string)
                    if (is_object($localCommittee)) {
                        $requestedLocalityId = $localCommittee->locality_id;
                    } else {
                        // Si c'est un ID, charger l'objet
                        $committeeModel = LocalCommittee::find($localCommittee);
                        $requestedLocalityId = $committeeModel ? $committeeModel->locality_id : null;
                    }
                
                    $meeting = $request->route('meeting');
                    // Vérifier si $meeting est un objet ou un ID (string)
                    if (is_object($meeting)) {
                        $requestedLocalityId = $meeting->locality_id ?? ($meeting->local_committee ? $meeting->local_committee->locality_id : null);
                    } else {
                        // Si c'est un ID, charger l'objet
                        $meetingModel = Meeting::find($meeting);
                        if ($meetingModel) {
                            $requestedLocalityId = $meetingModel->locality_id ?? ($meetingModel->local_committee ? $meetingModel->local_committee->locality_id : null);
                        }
                    }
                } elseif ($request->route('committeeId')) {
                    // Pour les routes qui utilisent committeeId comme paramètre
                    $committeeId = $request->route('committeeId');
                    $committee = LocalCommittee::find($committeeId);
                    $requestedLocalityId = $committee ? $committee->locality_id : null;
                } elseif ($request->has('locality_id')) {
                    $requestedLocalityId = $request->input('locality_id');
                }
                
                // Si une localité est demandée et qu'elle ne correspond pas à celle de l'utilisateur
                if ($requestedLocalityId && !$this->checkLocalityAccess($user, $requestedLocalityId)) {
                    abort(403, "Vous n'avez pas accès à cette localité.");
                }
            } catch (\Exception $e) {
                // En cas d'erreur, on laisse passer pour éviter de bloquer l'accès
                Log::warning('Erreur lors de la vérification d\'accès à la localité: ' . $e->getMessage());
            }
        }
        
        return $next($request);
    }
    
    /**
     * Vérifie si l'utilisateur a accès à la localité demandée
     */
    private function checkLocalityAccess($user, $requestedLocalityId): bool
    {
        // Si les IDs correspondent directement, accès autorisé
        if ($user->locality_id == $requestedLocalityId) {
            return true;
        }
        
       
        
        return false;
    }
} 