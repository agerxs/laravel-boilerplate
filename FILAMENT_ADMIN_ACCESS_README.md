# Accès à l'interface admin Filament

## Problème résolu

L'admin dans le fast login de l'interface web n'arrivait pas à accéder à l'interface admin Filament à cause de conflits dans les seeders et d'incohérences dans les rôles utilisateur.

## Cause du problème

1. **Conflit de seeders** : Le `FilamentUserSeeder` et `AdminUsersSeeder` créaient des utilisateurs avec le même email `admin@colocs.ci`
2. **Rôles multiples** : L'utilisateur `admin@colocs.ci` avait à la fois les rôles `admin` ET `super_admin`
3. **Attribut incohérent** : L'utilisateur avait le rôle `super_admin` mais `is_super_admin = false`

## Solution appliquée

### 1. Correction du FilamentUserSeeder

- Création d'utilisateurs avec des emails uniques pour éviter les conflits
- Attribution correcte des rôles et attributs
- Utilisation d'emails dédiés pour Filament

### 2. Nouveaux utilisateurs admin Filament

#### Super Admin Filament
- **Email** : `filament.admin@colocs.ci`
- **Mot de passe** : `password`
- **Rôle** : `super_admin`
- **Attribut** : `is_super_admin = true`
- **Accès** : Accès complet à l'interface Filament

#### Admin Simple Filament
- **Email** : `filament.admin.simple@colocs.ci`
- **Mot de passe** : `password`
- **Rôle** : `admin`
- **Attribut** : `is_super_admin = false`
- **Accès** : Accès limité à l'interface Filament

## Accès à l'interface

### URL
```
http://votre-domaine.com/admin
```

### Identifiants recommandés
```
Email: filament.admin@colocs.ci
Mot de passe: password
```

### Fast Login (Connexion rapide)

L'interface de connexion web a été mise à jour avec des boutons de connexion rapide :

1. **Admin Filament** (Bouton bleu indigo) : `filament.admin@colocs.ci` / `password`
   - Accès complet à l'interface Filament
   - Rôle : `super_admin`

2. **Admin Simple** (Bouton bleu) : `filament.admin.simple@colocs.ci` / `password`
   - Accès limité à l'interface Filament
   - Rôle : `admin`

3. **Président** (Bouton jaune) : `president@test.com` / `password123`
4. **Secrétaire** (Bouton vert) : `secretaire@test.com` / `password123`
5. **Trésorier** (Bouton violet) : `tresorier@test.com` / `password123`

## Vérification de la configuration

### 1. Vérifier que l'utilisateur existe
```bash
php artisan tinker
>>> $user = \App\Models\User::where('email', 'filament.admin@colocs.ci')->first();
>>> echo "ID: " . $user->id . "\n";
>>> echo "is_super_admin: " . ($user->is_super_admin ? 'true' : 'false') . "\n";
>>> echo "Rôles: " . $user->roles->pluck('name')->implode(', ') . "\n";
```

### 2. Vérifier les permissions
```bash
php artisan tinker
>>> $user = \App\Models\User::where('email', 'filament.admin@colocs.ci')->first();
>>> echo "Peut voir le dashboard: " . ($user->canViewDashboard() ? 'Oui' : 'Non') . "\n";
>>> echo "Peut modifier le dashboard: " . ($user->canModifyDashboard() ? 'Oui' : 'Non') . "\n";
```

## Structure des rôles

### Rôle `super_admin`
- Accès complet à toutes les fonctionnalités
- Peut créer, modifier, supprimer
- `is_super_admin = true`

### Rôle `admin`
- Accès en lecture et modification limitée
- Ne peut pas supprimer les éléments système
- `is_super_admin = false`

## Maintenance

### Réinitialiser les utilisateurs admin
```bash
# Supprimer les utilisateurs existants (optionnel)
php artisan tinker
>>> \App\Models\User::where('email', 'like', 'filament.%')->delete();

# Recréer les utilisateurs
php artisan db:seed --class=FilamentUserSeeder
```

### Mettre à jour un mot de passe
```bash
php artisan tinker
>>> $user = \App\Models\User::where('email', 'filament.admin@colocs.ci')->first();
>>> $user->update(['password' => Hash::make('nouveau_mot_de_passe')]);
```

## Notes importantes

- **Ne jamais utiliser** `admin@colocs.ci` pour Filament (utilisateur avec conflits)
- **Toujours utiliser** `filament.admin@colocs.ci` pour l'accès admin complet
- Les utilisateurs Filament sont séparés des utilisateurs de l'application web
- Changer le mot de passe par défaut en production

## Dépannage

### Problème : "Access denied" dans Filament
1. Vérifier que l'utilisateur a `is_super_admin = true`
2. Vérifier que l'utilisateur a le rôle `super_admin`
3. Vérifier que l'email correspond exactement

### Problème : Rôles non reconnus
1. Vérifier que les rôles existent dans la table `roles`
2. Vérifier que les rôles sont assignés dans `model_has_roles`
3. Exécuter `php artisan permission:cache-reset` si nécessaire

## Modifications récentes

### Suppression de la page de test des permissions
- **Page supprimée** : `TestPermissions.php` (interface de test des permissions admin)
- **Vue supprimée** : `test-permissions.blade.php`
- **Raison** : Nettoyage de l'interface admin, suppression des éléments de test
- **Impact** : Plus de menu "Test des Permissions" dans la navigation Filament

### Configuration simplifiée des rôles uniquement
- **RoleResource personnalisé** : `app/Filament/Resources/RoleResource.php`
- **Plugin Spatie désactivé** : Plus de plugin complexe
- **Configuration** : `config/filament-spatie-roles-permissions.php` simplifiée

#### Permissions configurées

| Action | Super Admin | Admin Simple |
|--------|-------------|--------------|
| **Créer** rôles | ✅ Oui | ❌ Non |
| **Modifier** rôles | ✅ Oui | ❌ Non |
| **Supprimer** rôles | ✅ Oui | ❌ Non |
| **Voir** rôles | ✅ Oui | ✅ Oui (lecture seule) |

#### Ressources personnalisées
- **RoleResource** : Hérite de `Filament\Resources\Resource` avec restrictions d'accès
- **Pages personnalisées** : ListRoles, CreateRole, EditRole, ViewRole
- **Navigation** : Groupée sous "Gestion des utilisateurs" avec icône shield-check
- **Pas de permissions** : Interface simplifiée, gestion des rôles uniquement
