<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RoleManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    protected RoleManagementService $roleService;

    public function __construct(RoleManagementService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Récupère tous les rôles avec leurs statistiques
     */
    public function index(): JsonResponse
    {
        try {
            $roles = $this->roleService->getRolesWithCounts();
            
            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Rôles récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rôles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les rôles suggérés (les plus populaires)
     */
    public function suggested(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $roles = $this->roleService->getSuggestedRoles($limit);
            
            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Rôles suggérés récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rôles suggérés: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche des rôles par terme
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $term = $request->get('term', '');
            $limit = $request->get('limit', 20);
            
            if (empty($term)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le terme de recherche est requis'
                ], 400);
            }
            
            $roles = $this->roleService->searchRoles($term, $limit);
            
            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Recherche de rôles effectuée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche de rôles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les statistiques des rôles
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->roleService->getRoleStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistiques des rôles récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les rôles par catégorie
     */
    public function byCategory(): JsonResponse
    {
        try {
            $categorizedRoles = $this->roleService->getRolesByCategory();
            
            return response()->json([
                'success' => true,
                'data' => $categorizedRoles,
                'message' => 'Rôles par catégorie récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rôles par catégorie: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les rôles les plus récents
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $roles = $this->roleService->getRecentRoles($limit);
            
            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Rôles récents récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rôles récents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Génère des suggestions contextuelles de rôles
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $partialRole = $request->get('partial_role', '');
            $context = $request->get('context', '');
            
            if (empty($partialRole)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le rôle partiel est requis'
                ], 400);
            }
            
            $suggestions = $this->roleService->getContextualSuggestions($partialRole, $context);
            
            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'message' => 'Suggestions de rôles générées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des suggestions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalise un rôle
     */
    public function normalize(Request $request): JsonResponse
    {
        try {
            $role = $request->get('role', '');
            
            if (empty($role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le rôle est requis'
                ], 400);
            }
            
            $normalizedRole = $this->roleService->normalizeRole($role);
            $exists = $this->roleService->roleExists($role);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'original' => $role,
                    'normalized' => $normalizedRole,
                    'exists' => $exists
                ],
                'message' => 'Rôle normalisé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la normalisation du rôle: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifie si un rôle existe
     */
    public function exists(Request $request): JsonResponse
    {
        try {
            $role = $request->get('role', '');
            
            if (empty($role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le rôle est requis'
                ], 400);
            }
            
            $exists = $this->roleService->roleExists($role);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'role' => $role,
                    'exists' => $exists
                ],
                'message' => 'Vérification effectuée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du rôle: ' . $e->getMessage()
            ], 500);
        }
    }
}
