# Utilisateurs de Test - Guide d'utilisation

Ce document explique comment utiliser le seeder `TestUsersSeeder` pour créer des comptes de test pour chaque profil utilisateur dans l'application.

## Vue d'ensemble

Le `TestUsersSeeder` crée automatiquement :
- Une hiérarchie complète de localités de test (Région > Département > Sous-Préfecture > Village)
- Utilise un comité local existant ou en crée un si nécessaire
- Des utilisateurs de test pour chaque rôle disponible
- Les associations appropriées entre utilisateurs, localités et comités

## Profils d'utilisateurs créés

| Rôle | Email | Téléphone | Description |
|------|-------|-----------|-------------|
| **Admin** | admin@test.com | 0700000001 | Administrateur système |
| **Président** | president@test.com | 0700000002 | Président du comité local |
| **Sous-Préfet** | sousprefet@test.com | 0700000003 | Sous-préfet de la localité |
| **Secrétaire** | secretaire@test.com | 0700000004 | Secrétaire du comité local |
| **Tresorier** | tresorier@test.com | 0700000005 | Tresorier des réunions |
| **Trésorier** | tresorier@test.com | 0700000006 | Trésorier du comité local |
| **Superviseur** | superviseur@test.com | 0700000007 | Superviseur régional |

## Mot de passe

Tous les utilisateurs de test utilisent le même mot de passe : **`password123`**

## Localités créées

- **Région Test** → **Département Test** → **Sous-Préfecture Test** → **Villages Test** (3 villages)
  - Village Test 1
  - Village Test 2  
  - Village Test 3

## Comité local

- **Utilisation** : Le seeder recherche d'abord un comité local existant dans la hiérarchie des localités
- **Création automatique** : Si aucun comité n'existe, un comité de base est créé automatiquement
- **Membres** : Les utilisateurs sont ajoutés au comité selon la configuration (admin et superviseur ne sont pas membres par défaut)

## Utilisation

### 1. Exécution automatique

Le seeder est automatiquement exécuté lors de l'utilisation de :

```bash
php artisan db:seed
```

### 2. Exécution manuelle

Pour exécuter uniquement ce seeder :

```bash
php artisan db:seed --class=TestUsersSeeder
```

### 3. Exécution en mode développement

```bash
php artisan db:seed --class=TestUsersSeeder --env=local
```

### 4. Test rapide avec le script PHP

```bash
php test-seeder.php
```

## Fonctionnalités

### Création intelligente

- **Vérification d'existence** : Si un utilisateur existe déjà, ses informations sont mises à jour
- **Création des rôles** : Les rôles sont créés automatiquement s'ils n'existent pas
- **Association automatique** : Les utilisateurs sont automatiquement ajoutés au comité local
- **Gestion des erreurs** : Les erreurs sont loggées et affichées dans la console

### Sécurité

- **Mots de passe hashés** : Utilisation de `bcrypt()` pour le hachage des mots de passe
- **Vérification des emails** : Tous les comptes sont marqués comme vérifiés
- **Rôles appropriés** : Chaque utilisateur reçoit le rôle correspondant à son profil

## Personnalisation

### Modifier le nombre de villages

Le système crée par défaut **3 villages de test**. Pour modifier ce nombre :

1. **Éditez le fichier de configuration** `config/test-users.php`
2. **Modifiez la section `village`** selon vos besoins
3. **Consultez** `example-villages-config.php` pour des exemples de configurations

```php
'village' => [
    'names' => [
        'Village Test 1',
        'Village Test 2', 
        'Village Test 3',
        'Village Test 4',  // Ajoutez plus de villages
        'Village Test 5'
    ],
    'display_name' => 'Village',
],
```

### Modifier les utilisateurs de test

Pour ajouter ou modifier des utilisateurs de test, éditez la méthode `createTestUsers()` dans `TestUsersSeeder.php` :

```php
$testUsers = [
    [
        'name' => 'Nouveau Rôle Test',
        'email' => 'nouveau@test.com',
        'phone' => '0700000008',
        'role' => 'nouveau-role',
        'description' => 'Description du nouveau rôle'
    ],
    // ... autres utilisateurs
];
```

### Modifier la hiérarchie des localités

Pour modifier la structure des localités de test, éditez le fichier de configuration `config/test-users.php` :

```php
// Exemple : modifier les villages
'village' => [
    'names' => [
        'Mon Village 1',
        'Mon Village 2',
        'Mon Village 3',
        'Mon Village 4'  // Ajouter un 4ème village
    ],
    'display_name' => 'Village',
],

// Exemple : ajouter une commune
'commune' => [
    'names' => [
        'Commune Test 1',
        'Commune Test 2'
    ],
    'display_name' => 'Commune',
],
```

Ou modifiez directement la méthode `createTestLocalityHierarchy()` dans le seeder.

## Dépannage

### Erreurs courantes

1. **Rôle non trouvé** : Vérifiez que le `RoleSeeder` a été exécuté avant
2. **Localité non trouvée** : Vérifiez que le `LocalitySeeder` a été exécuté avant
3. **Permissions insuffisantes** : Vérifiez les droits d'accès à la base de données

### Logs

Le seeder affiche des informations détaillées dans la console :
- Création des localités
- Création des utilisateurs
- Attribution des rôles
- Ajout au comité local
- Erreurs éventuelles

## Nettoyage

Pour supprimer les données de test :

```bash
php artisan migrate:fresh --seed
```

⚠️ **Attention** : Cette commande supprime toutes les données de la base et recrée la structure complète.

## Support

En cas de problème avec ce seeder, vérifiez :
1. Les logs de la console
2. La structure de la base de données
3. Les permissions des modèles
4. L'ordre d'exécution des seeders
