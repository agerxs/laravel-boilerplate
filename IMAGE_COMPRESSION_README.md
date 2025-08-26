# Compression d'Images - Documentation

## Vue d'ensemble

Ce système de compression d'images permet d'optimiser automatiquement les images uploadées dans l'application, réduisant leur taille tout en maintenant une qualité acceptable. La compression est appliquée lors de la sauvegarde des images.

## Fonctionnalités

### Types d'images supportés
- **Photos de présence** : Compressées pour les réunions
- **Photos de profil** : Optimisées pour les utilisateurs
- **Documents** : Images dans les pièces jointes

### Paramètres de compression

#### Photos de présence
- **Taille maximale** : 800x600 pixels
- **Qualité JPEG** : 80%
- **Format de sortie** : JPEG
- **Dossier de destination** : `storage/app/public/presence-photos/`

#### Photos de profil
- **Taille maximale** : 400x400 pixels
- **Qualité JPEG** : 85%
- **Format de sortie** : JPEG
- **Dossier de destination** : `storage/app/public/profile-photos/`

#### Documents
- **Taille maximale** : 1200x800 pixels
- **Qualité JPEG** : 90%
- **Format de sortie** : JPEG
- **Dossier de destination** : `storage/app/public/documents/`

## Utilisation

### Dans les contrôleurs

```php
use App\Services\ImageCompressionService;

class MeetingController extends Controller
{
    protected ImageCompressionService $imageCompressionService;

    public function __construct(ImageCompressionService $imageCompressionService)
    {
        $this->imageCompressionService = $imageCompressionService;
    }

    public function storePhoto(Request $request)
    {
        $photo = $request->file('photo');
        
        // Compression automatique
        $result = $this->imageCompressionService->compressPresencePhoto($photo);
        
        if ($result['success']) {
            $photoPath = $result['compressed_path'];
            // Utiliser le chemin compressé
        }
    }
}
```

### Méthodes disponibles

#### `compressPresencePhoto($file)`
Compresse une photo de présence avec les paramètres optimisés.

#### `compressProfilePhoto($file)`
Compresse une photo de profil.

#### `compressDocument($file)`
Compresse un document image.

#### `compressImage($file, $maxWidth, $maxHeight, $quality, $format)`
Méthode générique pour la compression personnalisée.

## Structure de réponse

```php
[
    'success' => true,
    'compressed_path' => 'presence-photos/compressed_image.jpg',
    'compressed_size' => 150000, // bytes
    'compression_ratio' => 75, // pourcentage
    'message' => 'Image compressée avec succès'
]
```

## Gestion des erreurs

En cas d'échec de compression :
```php
[
    'success' => false,
    'message' => 'Description de l\'erreur'
]
```

## Configuration

### Dépendances requises
- `intervention/image` : Package Laravel pour le traitement d'images
- `gd` ou `imagick` : Extension PHP pour le traitement d'images

### Installation
```bash
composer require intervention/image
php artisan vendor:publish --provider="Intervention\Image\ImageServiceProviderLaravelRecent"
```

## Logs

Le service enregistre automatiquement :
- Le ratio de compression
- Les tailles avant/après
- Les erreurs de compression
- Les chemins des fichiers

## Tests

### Tester la compression
```bash
php artisan test --filter=ImageCompressionTest
```

### Vérifier la qualité
- Ouvrir les images compressées
- Vérifier que la qualité reste acceptable
- Comparer les tailles de fichiers

## Maintenance

### Nettoyage des fichiers temporaires
Les fichiers temporaires sont automatiquement supprimés après compression.

### Surveillance de l'espace disque
Surveiller régulièrement l'espace disque dans `storage/app/public/`.

## Dépannage

### Erreur "Class 'Intervention\Image\Facades\Image' not found"
```bash
composer dump-autoload
php artisan config:clear
```

### Images non compressées
Vérifier que le service est bien injecté dans le contrôleur.

### Qualité trop basse
Ajuster les paramètres de qualité dans le service.

## Évolutions futures

- Support des formats WebP
- Compression progressive
- Redimensionnement intelligent
- Cache des images compressées
- Compression asynchrone pour les gros volumes
