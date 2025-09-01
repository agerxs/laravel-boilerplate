# Résolution des Problèmes de Routes Filament

## Problème : Route [filament.admin.pages.test-permissions] not defined

### Description
Cette erreur se produit généralement quand une page Filament est créée mais que les routes ne sont pas correctement générées ou mises en cache.

### Causes Possibles
1. **Cache des routes** : Les routes Filament peuvent être mises en cache
2. **Cache de configuration** : La configuration Filament peut être mise en cache
3. **Cache général** : Le cache de l'application peut interférer

### Solution

#### 1. Vider tous les caches
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

#### 2. Vérifier que la page est correctement configurée
- La page doit être dans le dossier `app/Filament/Pages/`
- La classe doit étendre `Filament\Pages\Page`
- Le namespace doit être correct : `App\Filament\Pages`

#### 3. Vérifier l'enregistrement automatique
Dans `AdminPanelProvider.php`, vérifier que la découverte automatique est activée :
```php
->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
```

#### 4. Vérifier les routes
```bash
php artisan route:list --name=admin | grep test-permissions
```

### Exemple de Page Filament Correcte

```php
<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TestPermissions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Configuration';
    protected static ?string $navigationLabel = 'Test des Permissions';
    protected static ?string $title = 'Test des Permissions Admin';
    protected static ?string $slug = 'test-permissions';
    protected static string $view = 'filament.pages.test-permissions';
}
```

### Vérification de la Vue
- La vue doit exister dans `resources/views/filament/pages/`
- Le nom du fichier doit correspondre au slug : `test-permissions.blade.php`
- La vue doit utiliser le composant Filament : `<x-filament-panels::page>`

### Prévention
- Toujours vider le cache après avoir créé une nouvelle page Filament
- Vérifier que les namespaces sont corrects
- S'assurer que la structure des dossiers respecte les conventions Filament

### Commandes Utiles
```bash
# Lister toutes les routes admin
php artisan route:list --name=admin

# Vider tous les caches
php artisan optimize:clear

# Redémarrer le serveur de développement
php artisan serve
```

**Responsable** : Assistant IA Claude  
**Date** : Décembre 2024
