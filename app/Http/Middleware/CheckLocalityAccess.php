<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
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
        
        // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Si l'utilisateur est un admin ou a un rôle supérieur, permettre l'accès complet
        if ($user->hasRole(['admin', 'super-admin'])) {
            return $next($request);
        }
        
        // Pour les préfets et secrétaires, vérifier la localité
        if ($user->hasRole(['prefet', 'Prefet', 'sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire'])) {
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
                } elseif ($request->route('meeting')) {
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
                // Log de l'erreur pour faciliter le débogage
                \Illuminate\Support\Facades\Log::error('Erreur dans CheckLocalityAccess: ' . $e->getMessage(), [
                    'exception' => $e,
                    'user_id' => $user->id,
                    'route' => $request->route()->getName(),
                    'parameters' => $routeParams
                ]);
                
                // Redirection avec message d'erreur
                abort(403, "Une erreur est survenue lors de la vérification des permissions d'accès. Détails: " . $e->getMessage());
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
        
        // Pour les préfets, vérifier si la localité demandée est une sous-préfecture de leur département
        if ($user->hasRole(['prefet', 'Prefet'])) {
            $userLocality = Locality::find($user->locality_id);
            
            // Vérifier si la localité demandée est une sous-préfecture dans le département du préfet
            $requestedLocality = Locality::find($requestedLocalityId);
            
            // Si la localité demandée a comme parent la localité du préfet, alors c'est autorisé
            if ($requestedLocality && $requestedLocality->parent_id == $user->locality_id) {
                return true;
            }
        }
        
        return false;
    }
} 