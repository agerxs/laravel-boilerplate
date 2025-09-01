# Configuration des Utilisateurs Admin Filament - Système à 2 Niveaux

## Vue d'ensemble

Ce document décrit la configuration des utilisateurs administrateurs Filament dans l'application Colocs avec un système à **2 niveaux d'administration** distincts.

## Architecture des Niveaux d'Administration

### 🚀 **Niveau 1 : Super Admin**
- **Accès complet** à toutes les fonctionnalités Filament
- **Peut modifier** tous les éléments du dashboard
- **Gestion complète** des utilisateurs, rôles et permissions
- **Statut** : `is_super_admin = true` + rôle `super_admin`

### 🔒 **Niveau 2 : Admin Simple**
- **Accès en lecture seule** au dashboard Filament
- **Peut voir** les listes et détails des ressources
- **Ne peut PAS modifier** les éléments
- **Statut** : `is_super_admin = false` + rôle `admin`

## Utilisateurs Admin Configurés

### 1. Super Administrateur (admin@colocs.ci)
- **Email**: admin@colocs.ci
- **Nom**: Administrateur Système
- **Mot de passe**: password
- **Rôle**: super_admin
- **Statut Filament**: Super Admin (is_super_admin = true)
- **Niveau**: 1 (Accès complet)
- **Téléphone**: +225070000000

### 2. Administrateur Simple (admin.simple@colocs.ci)
- **Email**: admin.simple@colocs.ci
- **Nom**: Administrateur Simple
- **Mot de passe**: password
- **Rôle**: admin
- **Statut Filament**: Admin Simple (is_super_admin = false)
- **Niveau**: 2 (Lecture seule)
- **Téléphone**: +225070000001

### 3. Administrateur de Test (admin@test.com)
- **Email**: admin@test.com
- **Nom**: Admin Test
- **Mot de passe**: password123
- **Rôle**: admin
- **Statut Filament**: Super Admin (is_super_admin = true)
- **Niveau**: 1 (Accès complet)
- **Téléphone**: 0700000001

## Configuration Technique

### Migration
Une migration a été créée pour ajouter la colonne `is_super_admin` à la table `users` :
- **Fichier**: `database/migrations/2025_08_28_225127_add_is_super_admin_to_users_table.php`
- **Colonne**: `is_super_admin` (boolean, défaut: false)

### Seeders
Deux seeders ont été configurés :

1. **FilamentUserSeeder** : Crée les utilisateurs admin avec leurs niveaux respectifs
2. **TestUsersSeeder** : Crée les utilisateurs de test, y compris l'admin de test

### Modèle User
Le modèle User utilise le trait `HasSuperAdmin` d'Althinect FilamentSpatieRolesPermissions et inclut des méthodes de vérification des permissions :

```php
// Vérifier le niveau d'administration
$user->isSuperAdmin();        // Niveau 1
$user->isAdmin();             // Niveau 2
$user->canViewDashboard();    // Peut voir le dashboard
$user->canModifyDashboard();  // Peut modifier (Niveau 1 uniquement)
```

### Resources Filament
Les ressources Filament ont été configurées pour respecter les niveaux d'administration :

- **UserResource** : Actions de modification masquées pour les admins simples
- **PaymentRateResource** : Actions de modification masquées pour les admins simples
- **Actions conditionnelles** : Boutons d'édition/suppression visibles selon le niveau

### Middleware
Un middleware `FilamentAccessMiddleware` contrôle l'accès au dashboard selon les permissions de l'utilisateur.

## Utilisation

### Connexion à Filament
Tous les utilisateurs admin peuvent se connecter à l'interface Filament avec leurs identifiants respectifs.

### Permissions par Niveau

#### Niveau 1 - Super Admin
- ✅ **Accès complet** à toutes les ressources
- ✅ **Créer, modifier, supprimer** des utilisateurs
- ✅ **Créer, modifier, supprimer** des taux de paiement
- ✅ **Gestion des rôles et permissions**
- ✅ **Toutes les actions d'administration**

#### Niveau 2 - Admin Simple
- ✅ **Voir** la liste des utilisateurs
- ✅ **Voir** la liste des taux de paiement
- ✅ **Navigation** dans le dashboard
- ❌ **Impossible de créer** de nouveaux éléments
- ❌ **Impossible de modifier** les éléments existants
- ❌ **Impossible de supprimer** des éléments

### Page de Test
Une page de test des permissions est disponible à `/admin/test-permissions` pour vérifier le niveau d'accès de l'utilisateur connecté.

## Sécurité

⚠️ **Important** : Les mots de passe par défaut doivent être changés en production.

### Changer les mots de passe
```bash
# Via Tinker
php artisan tinker
$user = App\Models\User::where('email', 'admin@colocs.ci')->first();
$user->update(['password' => Hash::make('nouveau_mot_de_passe_securise')]);
```

## Maintenance

### Ajouter un nouvel utilisateur admin
1. Modifier le `FilamentUserSeeder` ou créer un nouveau seeder
2. Exécuter : `php artisan db:seed --class=FilamentUserSeeder`

### Vérifier les utilisateurs admin
```bash
php artisan tinker
# Super admins
App\Models\User::where('is_super_admin', true)->get(['id', 'name', 'email']);

# Admins simples
App\Models\User::where('is_super_admin', false)->whereHas('roles', function($q) {
    $q->where('name', 'admin');
})->get(['id', 'name', 'email']);
```

### Tester les permissions
```bash
php artisan tinker
$user = App\Models\User::where('email', 'admin.simple@colocs.ci')->first();
echo "Peut voir le dashboard: " . ($user->canViewDashboard() ? 'Oui' : 'Non') . PHP_EOL;
echo "Peut modifier: " . ($user->canModifyDashboard() ? 'Oui' : 'Non') . PHP_EOL;
```

## Dépannage

### Problème de connexion
- Vérifier que l'utilisateur a le rôle approprié
- Vérifier que `is_super_admin` est correctement configuré
- Vérifier les permissions Spatie

### Actions non visibles
- Vérifier le niveau d'administration de l'utilisateur
- Vérifier que les méthodes `canEdit()`, `canDelete()` retournent `true`
- Vérifier la configuration des actions dans les ressources

### Erreur de migration
- Exécuter : `php artisan migrate:status`
- Si nécessaire : `php artisan migrate:rollback` puis `php artisan migrate`

## Avantages du Système à 2 Niveaux

1. **Sécurité renforcée** : Les admins simples ne peuvent pas modifier les données sensibles
2. **Audit facilité** : Seuls les super admins peuvent effectuer des modifications
3. **Formation progressive** : Possibilité de former des admins simples avant de les promouvoir
4. **Contrôle granulaire** : Gestion fine des permissions selon les besoins
5. **Conformité** : Respect du principe de moindre privilège
