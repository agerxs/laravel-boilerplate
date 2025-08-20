# Ajout du champ Sexe pour les Représentants

Ce document décrit l'ajout du champ `gender` (sexe) pour les représentants des comités locaux dans l'application.

## 🎯 Objectif

Ajouter la possibilité de spécifier le sexe des représentants des comités locaux pour une meilleure gestion et analyse des données.

## 🔧 Modifications apportées

### 1. Base de données

#### Migration Laravel
- **Fichier** : `database/migrations/2025_01_27_000000_add_gender_to_representatives_table.php`
- **Action** : Ajout d'une colonne `gender` de type ENUM avec les valeurs 'M' (Masculin) et 'F' (Féminin)
- **Position** : Après la colonne `phone`

```sql
ALTER TABLE representatives ADD COLUMN gender ENUM('M', 'F') NULL AFTER phone;
```

#### SQLite (Flutter)
- **Fichier** : `colocs-mobile-fl/lib/services/database_helper.dart`
- **Action** : Ajout de la colonne `gender` lors de la création/mise à jour de la table
- **Type** : TEXT (pour la compatibilité avec SQLite)

### 2. Modèles

#### Laravel (PHP)
- **Fichier** : `app/Models/Representative.php`
- **Modifications** :
  - Ajout de `'gender'` dans `$fillable`
  - Ajout d'accesseurs pour le formatage du genre
  - `getGenderLabelAttribute()` : Retourne "Masculin", "Féminin" ou "Non spécifié"
  - `getGenderShortAttribute()` : Retourne "M", "F" ou "-"

#### Flutter (Dart)
- **Fichier** : `colocs-mobile-fl/lib/models/representative.dart`
- **Modifications** :
  - Ajout du champ `gender` dans la classe
  - Mise à jour du constructeur
  - Mise à jour des méthodes `toMap()`, `fromMap()`, `toJson()`, `fromJson()`, `copyWith()`

### 3. Contrôleurs

#### API Laravel
- **Fichier** : `app/Http/Controllers/Api/RepresentativeController.php`
- **Modification** : Ajout de la validation `'gender' => 'nullable|in:M,F'`

#### Contrôleur principal
- **Fichier** : `app/Http/Controllers/RepresentativeController.php`
- **Modifications** : Ajout de la validation du champ gender dans `store()` et `update()`

### 4. Interfaces utilisateur

#### Vue principale des représentants
- **Fichier** : `resources/js/Pages/Representatives/Index.vue`
- **Modifications** :
  - Ajout du champ sexe dans le formulaire (select avec options M/F)
  - Mise à jour de l'interface `Representative`
  - Mise à jour du formulaire et de la logique de sauvegarde

#### Composant RepresentativesManager
- **Fichier** : `resources/js/Components/RepresentativesManager.vue`
- **Modifications** :
  - Passage de 2 à 3 colonnes dans la grille des champs
  - Ajout du champ sexe entre téléphone et rôle
  - Mise à jour de l'interface et des fonctions de création

#### Vue VillageRepresentatives
- **Fichier** : `resources/js/Pages/LocalCommittees/VillageRepresentatives.vue`
- **Modifications** :
  - Passage de 3 à 4 colonnes dans la grille
  - Ajout du champ sexe dans les formulaires de création et d'édition

#### Vue Create des comités locaux
- **Fichier** : `resources/js/Pages/LocalCommittees/Create.vue`
- **Modifications** :
  - Mise à jour de l'interface `Representative`
  - Ajout du champ gender dans l'initialisation des représentants

#### Formulaire Flutter
- **Fichier** : `colocs-mobile-fl/lib/screens/representant_form.dart`
- **Modifications** :
  - Ajout d'un `DropdownButtonFormField` pour le sexe
  - Mise à jour de la logique de sauvegarde

## 📊 Structure des données

### Valeurs possibles
- **M** : Masculin
- **F** : Féminin
- **NULL** : Non spécifié (optionnel)

### Format d'affichage
- **Court** : M, F, -
- **Long** : Masculin, Féminin, Non spécifié

## 🚀 Utilisation

### 1. Création d'un représentant
```php
$representative = Representative::create([
    'name' => 'John Doe',
    'phone' => '0123456789',
    'gender' => 'M', // Nouveau champ
    'locality_id' => 1,
    'local_committee_id' => 1,
    'role' => 'Chef du village'
]);
```

### 2. Mise à jour
```php
$representative->update(['gender' => 'F']);
```

### 3. Affichage formaté
```php
echo $representative->gender_label; // "Masculin"
echo $representative->gender_short; // "M"
```

### 4. Validation
```php
$request->validate([
    'gender' => 'nullable|in:M,F'
]);
```

## 🔄 Migration

### Exécution de la migration
```bash
php artisan migrate
```

### Vérification
```bash
php artisan migrate:status
```

## 🧪 Tests

### Test de la migration
```bash
php artisan migrate:rollback --step=1
php artisan migrate
```

### Test des accesseurs
```php
$representative = Representative::first();
echo $representative->gender_label; // Doit afficher le label en français
echo $representative->gender_short; // Doit afficher M, F ou -
```

## 📱 Compatibilité mobile

Le champ est entièrement compatible avec l'application mobile Flutter :
- Synchronisation bidirectionnelle
- Stockage local SQLite
- Interface utilisateur adaptée

## 🔍 Filtres et recherche

Le champ peut être utilisé pour :
- Filtrer les représentants par sexe
- Générer des statistiques de parité
- Analyser la répartition des rôles par genre

## 📈 Évolutions futures

Possibilités d'extension :
- Ajout d'autres valeurs (ex: "Autre", "Non-binaire")
- Intégration avec des indicateurs de parité
- Rapports et tableaux de bord par genre

## ⚠️ Notes importantes

1. **Rétrocompatibilité** : Le champ est nullable, les données existantes ne sont pas affectées
2. **Validation** : Seules les valeurs 'M' et 'F' sont acceptées
3. **Interface** : Tous les formulaires ont été mis à jour pour inclure ce champ
4. **Mobile** : L'application Flutter a été mise à jour en parallèle

## 🎉 Résumé

L'ajout du champ sexe pour les représentants est maintenant complet et fonctionnel sur :
- ✅ Backend Laravel (API + Web)
- ✅ Frontend Vue.js
- ✅ Application mobile Flutter
- ✅ Base de données (MySQL + SQLite)
- ✅ Validation et accesseurs
- ✅ Interface utilisateur complète
