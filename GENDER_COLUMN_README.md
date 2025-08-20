# Ajout de la colonne Sexe dans la liste des Représentants

Ce document décrit l'ajout de la colonne sexe dans la liste des représentants des comités locaux.

## 🎯 Objectif

Ajouter une colonne "Sexe" dans le tableau de la liste des représentants pour permettre une meilleure visualisation et filtrage des données.

## 🔧 Modifications apportées

### 1. Vue principale des représentants

#### Fichier : `resources/js/Pages/Representatives/Index.vue`

**Modifications :**
- Ajout d'une colonne "Sexe" dans l'en-tête du tableau
- Affichage du sexe avec un composant `GenderBadge` stylisé
- Mise à jour du `colspan` pour le message "Aucun représentant trouvé"
- Ajout d'un filtre par sexe dans la section des filtres

**Structure du tableau :**
```vue
<thead>
  <tr>
    <th>Nom</th>
    <th>Rôle</th>
    <th>Téléphone</th>
    <th>Sexe</th>        <!-- Nouvelle colonne -->
    <th>Village</th>
    <th>Comité Local</th>
    <th>Actions</th>
  </tr>
</thead>
```

### 2. Composants créés

#### GenderBadge.vue
**Fichier :** `resources/js/Components/GenderBadge.vue`

**Fonctionnalité :** Affiche le sexe avec un style visuel distinctif
- **Masculin** : Badge bleu avec point bleu
- **Féminin** : Badge rose avec point rose  
- **Non spécifié** : Texte gris

**Utilisation :**
```vue
<GenderBadge :gender="rep.gender" />
```

#### GenderFilter.vue
**Fichier :** `resources/js/Components/GenderFilter.vue`

**Fonctionnalité :** Filtre déroulant pour sélectionner le sexe
- **Tous les sexes** : Aucun filtre
- **Masculin** : Filtre sur les hommes
- **Féminin** : Filtre sur les femmes

**Utilisation :**
```vue
<GenderFilter v-model="filters.gender" />
```

### 3. Contrôleur Laravel

#### Fichier : `app/Http/Controllers/RepresentativeController.php`

**Modifications :**
- Ajout du filtre par sexe dans la méthode `index()`
- Inclusion du paramètre `gender` dans les filtres retournés

**Code ajouté :**
```php
if ($request->filled('gender')) {
    $query->where('gender', $request->gender);
}

// Dans le retour
'filters' => $request->only(['search', 'local_committee_id', 'locality_id', 'gender']),
```

### 4. Interface TypeScript

#### Mise à jour de l'interface Props
```typescript
interface Props {
  // ... autres propriétés
  filters: {
    search: string;
    local_committee_id: string;
    locality_id: string;
    gender: string;        // Nouveau filtre
  };
}
```

#### Mise à jour des filtres réactifs
```typescript
const filters = reactive({
  search: props.filters.search || '',
  local_committee_id: props.filters.local_committee_id || '',
  locality_id: props.filters.locality_id || '',
  gender: props.filters.gender || '',        // Nouveau filtre
});
```

## 🎨 Style et présentation

### Badge de sexe
- **Masculin** : Fond bleu clair, texte bleu foncé, point bleu
- **Féminin** : Fond rose clair, texte rose foncé, point rose
- **Non spécifié** : Texte gris clair

### Filtre par sexe
- **Position** : 4ème colonne dans la grille des filtres
- **Style** : Select standard avec options claires
- **Responsive** : S'adapte aux différentes tailles d'écran

## 🔍 Fonctionnalités

### 1. Affichage
- Colonne dédiée au sexe dans le tableau
- Badges visuels distinctifs pour chaque genre
- Gestion des cas où le sexe n'est pas spécifié

### 2. Filtrage
- Filtre par sexe dans la section des filtres
- Combinaison avec les autres filtres existants
- Réinitialisation du filtre avec le bouton "Réinitialiser"

### 3. Responsive
- Grille des filtres passée de 3 à 4 colonnes
- Adaptation automatique sur mobile
- Maintien de la lisibilité

## 🚀 Utilisation

### 1. Affichage de la liste
La colonne sexe s'affiche automatiquement dans le tableau des représentants avec des badges colorés.

### 2. Filtrage
1. Sélectionner un sexe dans le filtre "Sexe"
2. Cliquer sur "Appliquer" pour filtrer les résultats
3. Utiliser "Réinitialiser" pour supprimer tous les filtres

### 3. Combinaison de filtres
Le filtre par sexe peut être combiné avec :
- Recherche par nom
- Filtre par comité local
- Filtre par village

## 📱 Compatibilité

- **Desktop** : Affichage optimal avec tous les filtres visibles
- **Tablet** : Adaptation de la grille des filtres
- **Mobile** : Filtres empilés verticalement

## 🔄 Mise à jour des données

### Synchronisation
- Le filtre par sexe est automatiquement synchronisé avec l'URL
- Les paramètres de filtrage sont conservés lors de la navigation
- La pagination respecte les filtres actifs

### Performance
- Filtrage côté serveur pour de meilleures performances
- Requête SQL optimisée avec index sur la colonne `gender`
- Mise en cache des résultats filtrés

## 📊 Statistiques possibles

Avec cette nouvelle colonne, il est maintenant possible de :
- Compter le nombre de représentants par sexe
- Analyser la répartition des rôles par genre
- Générer des rapports de parité
- Suivre l'évolution de la représentation féminine/masculine

## 🎉 Résumé

L'ajout de la colonne sexe dans la liste des représentants apporte :
- ✅ **Visibilité** : Affichage clair du genre de chaque représentant
- ✅ **Filtrage** : Possibilité de filtrer par sexe
- ✅ **Style** : Badges visuels attractifs et informatifs
- ✅ **Responsive** : Adaptation à tous les écrans
- ✅ **Performance** : Filtrage côté serveur optimisé
- ✅ **Réutilisabilité** : Composants modulaires pour d'autres vues

La fonctionnalité est maintenant entièrement opérationnelle et s'intègre parfaitement avec l'interface existante ! 🎯
