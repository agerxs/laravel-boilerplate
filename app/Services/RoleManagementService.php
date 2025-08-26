<?php

namespace App\Services;

use App\Models\Representative;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RoleManagementService
{
    /**
     * Récupère tous les rôles existants avec leur nombre d'occurrences
     */
    public function getRolesWithCounts(): Collection
    {
        return Representative::select('role', DB::raw('count(*) as count'))
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->groupBy('role')
            ->orderBy('count', 'desc')
            ->orderBy('role', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->role,
                    'label' => $this->formatRoleLabel($item->role),
                    'count' => $item->count
                ];
            });
    }

    /**
     * Récupère les rôles suggérés (les plus populaires)
     */
    public function getSuggestedRoles(int $limit = 10): Collection
    {
        return $this->getRolesWithCounts()
            ->take($limit)
            ->values();
    }

    /**
     * Recherche des rôles par terme
     */
    public function searchRoles(string $term, int $limit = 20): Collection
    {
        return Representative::select('role', DB::raw('count(*) as count'))
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->where('role', 'like', '%' . $term . '%')
            ->groupBy('role')
            ->orderBy('count', 'desc')
            ->orderBy('role', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->role,
                    'label' => $this->formatRoleLabel($item->role),
                    'count' => $item->count
                ];
            });
    }

    /**
     * Récupère les statistiques des rôles
     */
    public function getRoleStatistics(): array
    {
        $totalRepresentatives = Representative::whereNotNull('role')->where('role', '!=', '')->count();
        $uniqueRoles = Representative::whereNotNull('role')->where('role', '!=', '')->distinct('role')->count();
        
        $topRoles = Representative::select('role', DB::raw('count(*) as count'))
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->groupBy('role')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) use ($totalRepresentatives) {
                return [
                    'role' => $item->role,
                    'count' => $item->count,
                    'percentage' => round(($item->count / $totalRepresentatives) * 100, 1)
                ];
            });

        return [
            'total_representatives' => $totalRepresentatives,
            'unique_roles' => $uniqueRoles,
            'top_roles' => $topRoles,
            'average_per_role' => $totalRepresentatives > 0 ? round($totalRepresentatives / $uniqueRoles, 1) : 0
        ];
    }

    /**
     * Normalise un rôle (première lettre en majuscule, reste en minuscule)
     */
    public function normalizeRole(string $role): string
    {
        return ucfirst(strtolower(trim($role)));
    }

    /**
     * Vérifie si un rôle existe déjà
     */
    public function roleExists(string $role): bool
    {
        return Representative::where('role', $this->normalizeRole($role))->exists();
    }

    /**
     * Ajoute un nouveau rôle (optionnel, pour la traçabilité)
     */
    public function addNewRole(string $role): void
    {
        // Ici on pourrait ajouter une logique pour tracer les nouveaux rôles
        // Par exemple, les logger ou les stocker dans une table séparée
        \Illuminate\Support\Facades\Log::info('Nouveau rôle ajouté', ['role' => $this->normalizeRole($role)]);
    }

    /**
     * Récupère les rôles par catégorie (pour les suggestions intelligentes)
     */
    public function getRolesByCategory(): array
    {
        $roles = $this->getRolesWithCounts();
        
        $categories = [
            'leadership' => ['chef', 'président', 'directeur', 'responsable', 'coordinateur'],
            'demographic' => ['femme', 'jeune', 'homme', 'adulte', 'senior'],
            'functional' => ['secrétaire', 'trésorier', 'membre', 'délégué', 'représentant'],
            'sectorial' => ['agriculture', 'commerce', 'éducation', 'santé', 'environnement']
        ];

        $categorizedRoles = [];
        
        foreach ($categories as $category => $keywords) {
            $categorizedRoles[$category] = $roles->filter(function ($role) use ($keywords) {
                $roleLower = strtolower($role['value']);
                return collect($keywords)->contains(function ($keyword) use ($roleLower) {
                    return str_contains($roleLower, $keyword);
                });
            })->values();
        }

        return $categorizedRoles;
    }

    /**
     * Formate le label d'un rôle pour l'affichage
     */
    private function formatRoleLabel(string $role): string
    {
        // Première lettre en majuscule
        $formatted = ucfirst(strtolower(trim($role)));
        
        // Gestion des cas spéciaux
        $replacements = [
            'chef du village' => 'Chef du village',
            'representant des femmes' => 'Représentant des femmes',
            'representant des jeunes' => 'Représentant des jeunes',
            'membre des femmes' => 'Membre des femmes',
            'membre des jeunes' => 'Membre des jeunes'
        ];

        return $replacements[strtolower($formatted)] ?? $formatted;
    }

    /**
     * Récupère les rôles les plus récents (pour les suggestions)
     */
    public function getRecentRoles(int $limit = 10): Collection
    {
        return Representative::select('role', DB::raw('max(created_at) as last_used'))
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->groupBy('role')
            ->orderBy('last_used', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->role,
                    'label' => $this->formatRoleLabel($item->role),
                    'last_used' => $item->last_used
                ];
            });
    }

    /**
     * Génère des suggestions de rôles basées sur le contexte
     */
    public function getContextualSuggestions(string $partialRole, string $context = ''): Collection
    {
        $suggestions = collect();
        
        // Recherche exacte
        $exactMatches = $this->searchRoles($partialRole, 5);
        $suggestions = $suggestions->merge($exactMatches);
        
        // Suggestions basées sur le contexte
        if ($context) {
            $contextualRoles = $this->getRolesByCategory();
            
            foreach ($contextualRoles as $category => $roles) {
                if (str_contains(strtolower($context), $category)) {
                    $suggestions = $suggestions->merge($roles->take(3));
                }
            }
        }
        
        // Rôles populaires si pas assez de suggestions
        if ($suggestions->count() < 5) {
            $popularRoles = $this->getSuggestedRoles(5);
            $suggestions = $suggestions->merge($popularRoles);
        }
        
        // Déduplication et limitation
        return $suggestions->unique('value')->take(10)->values();
    }
}
