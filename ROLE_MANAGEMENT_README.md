# Gestion des Rôles avec Select2Input et Autocomplétion

## Vue d'ensemble

Cette fonctionnalité transforme le champ "rôle" des représentants d'un simple select avec options prédéfinies en un composant Select2 intelligent avec autocomplétion, suggestions et possibilité de saisie libre. Cela permet de standardiser les rôles tout en gardant la flexibilité pour l'utilisateur et facilite les futures analyses statistiques.

## Fonctionnalités principales

### 1. **Composant Select2Input personnalisé**
- **Recherche en temps réel** : Filtrage instantané des options
- **Autocomplétion** : Suggestions basées sur la saisie
- **Saisie libre** : Possibilité d'ajouter de nouveaux rôles
- **Navigation clavier** : Flèches, Entrée, Échap
- **Interface intuitive** : Dropdown avec compteurs et options personnalisées

### 2. **Service de gestion des rôles**
- **Statistiques en temps réel** : Nombre d'occurrences par rôle
- **Suggestions intelligentes** : Rôles les plus populaires
- **Catégorisation** : Groupement par type (leadership, démographique, fonctionnel, sectoriel)
- **Normalisation** : Standardisation des formats de rôles
- **Traçabilité** : Journalisation des nouveaux rôles

### 3. **API REST complète**
- **Endpoints multiples** : Récupération, recherche, statistiques, suggestions
- **Gestion d'erreurs** : Réponses structurées avec codes d'état
- **Performance** : Requêtes optimisées avec pagination
- **Sécurité** : Authentification et autorisation

## Architecture technique

### 1. **Composant Vue.js Select2Input**

#### Structure du composant
```vue
<Select2Input
  v-model="rep.role"
  :options="roleOptions"
  label="Rôle"
  placeholder="Tapez pour rechercher un rôle..."
  help-text="Sélectionnez un rôle existant ou tapez un nouveau rôle"
  :isRequired="true"
  @custom-value="handleCustomRole"
/>
```

#### Fonctionnalités clés
- **Recherche en temps réel** avec filtrage côté client
- **Dropdown intelligent** avec options filtrées
- **Option personnalisée** pour ajouter de nouveaux rôles
- **Navigation clavier** complète (flèches, Entrée, Échap)
- **Gestion des clics extérieurs** pour fermer le dropdown
- **Affichage de la valeur sélectionnée** avec possibilité de suppression

#### Props disponibles
- `modelValue` : Valeur sélectionnée (v-model)
- `options` : Liste des options disponibles
- `label` : Label du champ
- `placeholder` : Texte d'aide dans l'input
- `helpText` : Texte d'aide sous le champ
- `error` : Message d'erreur
- `disabled` : État désactivé
- `isRequired` : Champ obligatoire
- `allowCustom` : Autoriser les valeurs personnalisées
- `showCounts` : Afficher les compteurs

#### Événements émis
- `update:modelValue` : Mise à jour de la valeur
- `custom-value` : Nouvelle valeur personnalisée saisie

### 2. **Service RoleManagementService**

#### Méthodes principales
```php
class RoleManagementService
{
    // Récupère tous les rôles avec leurs statistiques
    public function getRolesWithCounts(): Collection
    
    // Récupère les rôles suggérés (les plus populaires)
    public function getSuggestedRoles(int $limit = 10): Collection
    
    // Recherche des rôles par terme
    public function searchRoles(string $term, int $limit = 20): Collection
    
    // Récupère les statistiques des rôles
    public function getRoleStatistics(): array
    
    // Récupère les rôles par catégorie
    public function getRolesByCategory(): array
    
    // Normalise un rôle (première lettre en majuscule)
    public function normalizeRole(string $role): string
    
    // Vérifie si un rôle existe déjà
    public function roleExists(string $role): bool
    
    // Génère des suggestions contextuelles
    public function getContextualSuggestions(string $partialRole, string $context = ''): Collection
}
```

#### Catégorisation des rôles
- **Leadership** : chef, président, directeur, responsable, coordinateur
- **Démographique** : femme, jeune, homme, adulte, senior
- **Fonctionnel** : secrétaire, trésorier, membre, délégué, représentant
- **Sectoriel** : agriculture, commerce, éducation, santé, environnement

### 3. **Contrôleur API RoleController**

#### Endpoints disponibles
```
GET    /api/roles                    # Tous les rôles avec statistiques
GET    /api/roles/suggested         # Rôles suggérés (populaires)
GET    /api/roles/search            # Recherche de rôles
GET    /api/roles/statistics        # Statistiques complètes
GET    /api/roles/by-category       # Rôles par catégorie
GET    /api/roles/recent            # Rôles récents
GET    /api/roles/suggestions       # Suggestions contextuelles
POST   /api/roles/normalize         # Normalisation d'un rôle
POST   /api/roles/exists            # Vérification d'existence
```

#### Structure des réponses
```json
{
  "success": true,
  "data": [...],
  "message": "Message de succès"
}
```

## Implémentation dans les composants

### 1. **RepresentativesManager.vue**

#### Intégration du composant
```vue
<Select2Input
  v-model="rep.role"
  :options="roleOptions"
  label="Rôle"
  placeholder="Tapez pour rechercher un rôle..."
  help-text="Sélectionnez un rôle existant ou tapez un nouveau rôle"
  :isRequired="true"
  @custom-value="handleCustomRole"
/>
```

#### Logique de gestion des rôles
```javascript
// Chargement des rôles disponibles
const loadRoleOptions = async () => {
  try {
    const response = await fetch('/api/roles/suggested?limit=20');
    if (response.ok) {
      const data = await response.json();
      if (data.success) {
        roleOptions.value = data.data;
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des rôles:', error);
  }
};

// Gestion des rôles personnalisés
const handleCustomRole = (customRole: string) => {
  const newRole = {
    value: customRole,
    label: customRole,
    count: 1
  };
  
  if (!roleOptions.value.some(role => 
    role.value.toLowerCase() === customRole.toLowerCase()
  )) {
    roleOptions.value.unshift(newRole);
  }
  
  logNewRole(customRole);
};
```

### 2. **VillageRepresentatives.vue**

#### Remplacement du select classique
```vue
<!-- Avant -->
<select v-model="rep.role" class="...">
  <option value="">Sélectionner un rôle</option>
  <option value="Chef du village">Chef du village</option>
  <option value="Représentant des femmes">Représentant des femmes</option>
  <option value="Représentant des jeunes">Représentant des jeunes</option>
  <option value="Autre">Autre</option>
</select>

<!-- Après -->
<Select2Input
  v-model="rep.role"
  :options="roleOptions"
  label="Rôle"
  placeholder="Tapez pour rechercher un rôle..."
  help-text="Sélectionnez un rôle existant ou tapez un nouveau rôle"
  :isRequired="true"
  @custom-value="handleCustomRole"
/>
```

## Avantages de cette approche

### 1. **Expérience utilisateur améliorée**
- **Recherche intuitive** : L'utilisateur trouve rapidement le rôle souhaité
- **Suggestions intelligentes** : Les rôles les plus populaires sont mis en avant
- **Flexibilité** : Possibilité d'ajouter de nouveaux rôles si nécessaire
- **Navigation clavier** : Support complet des raccourcis clavier

### 2. **Standardisation des données**
- **Normalisation automatique** : Formatage cohérent des rôles
- **Déduplication** : Évite les variations orthographiques
- **Traçabilité** : Suivi des nouveaux rôles ajoutés
- **Catégorisation** : Organisation logique des rôles

### 3. **Préparation aux analyses statistiques**
- **Compteurs en temps réel** : Nombre d'occurrences par rôle
- **Métadonnées enrichies** : Catégories, dates d'utilisation
- **API structurée** : Données facilement exploitables
- **Évolutivité** : Facilite l'ajout de nouvelles analyses

### 4. **Maintenance et évolutivité**
- **Code modulaire** : Composants réutilisables
- **Service centralisé** : Logique métier centralisée
- **API REST** : Interface standardisée et extensible
- **Gestion d'erreurs** : Robustesse et traçabilité

## Utilisation

### 1. **Sélection d'un rôle existant**
1. **Cliquer sur le champ** : Le dropdown s'ouvre avec les suggestions
2. **Taper pour rechercher** : Filtrage automatique des options
3. **Naviguer avec les flèches** : Sélection de l'option souhaitée
4. **Valider avec Entrée** : Sélection du rôle

### 2. **Ajout d'un nouveau rôle**
1. **Taper le nouveau rôle** : Saisie libre dans le champ
2. **Voir l'option personnalisée** : "Ajouter [rôle]"
3. **Cliquer sur l'option** : Le nouveau rôle est ajouté
4. **Automatiquement sélectionné** : Le rôle est immédiatement disponible

### 3. **Navigation clavier**
- **Flèche bas/haut** : Navigation dans les options
- **Entrée** : Sélection de l'option ou ajout du rôle personnalisé
- **Échap** : Fermeture du dropdown
- **Tab** : Navigation vers le champ suivant

## Configuration et personnalisation

### 1. **Options du composant Select2Input**
```javascript
// Configuration avancée
<Select2Input
  v-model="selectedRole"
  :options="roleOptions"
  label="Rôle"
  placeholder="Rechercher un rôle..."
  help-text="Aide contextuelle"
  :allowCustom="true"
  :showCounts="true"
  :disabled="false"
  :isRequired="true"
  @custom-value="handleCustomRole"
/>
```

### 2. **Personnalisation du service**
```php
// Dans RoleManagementService
protected $categories = [
    'leadership' => ['chef', 'président', 'directeur'],
    'demographic' => ['femme', 'jeune', 'homme'],
    // Ajouter de nouvelles catégories
];

protected $replacements = [
    'chef du village' => 'Chef du village',
    // Ajouter de nouveaux remplacements
];
```

### 3. **Limites et pagination**
```php
// Dans le contrôleur API
public function suggested(Request $request): JsonResponse
{
    $limit = $request->get('limit', 10); // Limite configurable
    $roles = $this->roleService->getSuggestedRoles($limit);
    // ...
}
```

## Tests et validation

### 1. **Tests du composant Select2Input**
- [ ] Ouverture/fermeture du dropdown
- [ ] Recherche et filtrage des options
- [ ] Navigation clavier (flèches, Entrée, Échap)
- [ ] Gestion des clics extérieurs
- [ ] Ajout de rôles personnalisés
- [ ] Validation des champs obligatoires

### 2. **Tests du service RoleManagementService**
- [ ] Récupération des rôles avec compteurs
- [ ] Recherche par terme
- [ ] Catégorisation automatique
- [ ] Normalisation des rôles
- [ ] Suggestions contextuelles
- [ ] Gestion des erreurs

### 3. **Tests de l'API**
- [ ] Endpoints accessibles et sécurisés
- [ ] Réponses JSON valides
- [ ] Gestion des erreurs et codes d'état
- [ ] Performance des requêtes
- [ ] Validation des paramètres

## Évolutions futures

### 1. **Améliorations de l'interface**
- **Recherche avancée** : Filtres par catégorie, popularité
- **Suggestions contextuelles** : Basées sur le village, comité local
- **Historique personnel** : Rôles utilisés récemment par l'utilisateur
- **Favoris** : Rôles marqués comme favoris

### 2. **Fonctionnalités avancées**
- **Synchronisation** : Partage des nouveaux rôles entre utilisateurs
- **Validation** : Règles de validation personnalisées
- **Workflow** : Processus d'approbation pour nouveaux rôles
- **Analytics** : Tableaux de bord et rapports avancés

### 3. **Intégrations**
- **Export/Import** : Gestion en lot des rôles
- **API externe** : Intégration avec d'autres systèmes
- **Notifications** : Alertes sur nouveaux rôles
- **Audit** : Traçabilité complète des modifications

## Conclusion

L'implémentation du composant Select2Input avec autocomplétion pour la gestion des rôles transforme l'expérience utilisateur tout en préparant l'application aux futures analyses statistiques. Cette approche combine la flexibilité de la saisie libre avec la standardisation des données, offrant une solution robuste et évolutive.

En centralisant la logique de gestion des rôles dans un service dédié et en exposant une API REST complète, l'application gagne en maintenabilité et en extensibilité. Les utilisateurs bénéficient d'une interface intuitive et performante, tandis que les développeurs disposent d'une architecture modulaire et bien structurée.
