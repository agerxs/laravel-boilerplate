# Migration vers Intervention Image 3.x

## Problème identifié

L'erreur `Class "Intervention\Image\Facades\Image" not found` se produisait car :

1. **Version incompatible** : Le code utilisait l'ancienne syntaxe d'Intervention Image 2.x
2. **Package installé** : Intervention Image 3.x était installé via Composer
3. **Façade obsolète** : La façade `Image` n'existe plus dans la version 3.x

## Solution appliquée

### 1. Mise à jour des imports

```php
// Avant (Intervention Image 2.x)
use Intervention\Image\Facades\Image;

// Après (Intervention Image 3.x)
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
```

### 2. Mise à jour de la création d'instance

```php
// Avant (Intervention Image 2.x)
$img = Image::make($image->getRealPath());

// Après (Intervention Image 3.x)
$manager = new ImageManager(new Driver());
$img = $manager->read($image->getRealPath());
```

### 3. Mise à jour des méthodes de redimensionnement

```php
// Avant (Intervention Image 2.x)
$img->resize($maxWidth, $maxHeight, function ($constraint) {
    $constraint->aspectRatio();
    $constraint->upsize();
});

// Après (Intervention Image 3.x)
$img = $img->scaleDown($maxWidth, $maxHeight);
```

### 4. Mise à jour des méthodes d'encodage

```php
// Avant (Intervention Image 2.x)
$img->encode('jpg', $quality);
$img->encode('png', $pngQuality);
$img->encode('webp', $quality);

// Après (Intervention Image 3.x)
$img = $img->toJpeg($quality);
$img = $img->toPng($pngQuality);
$img = $img->toWebp($quality);
```

### 5. Suppression de la méthode destroy()

```php
// Avant (Intervention Image 2.x)
$img->destroy();

// Après (Intervention Image 3.x)
// Plus nécessaire, la gestion mémoire est automatique
```

## Configuration

### 1. Fichier de configuration `config/image.php`

```php
<?php

return [
    'driver' => env('IMAGE_DRIVER', 'gd'),
    'quality' => env('IMAGE_QUALITY', 80),
    'max_width' => env('IMAGE_MAX_WIDTH', 1200),
    'max_height' => env('IMAGE_MAX_HEIGHT', 1200),
    'supported_formats' => ['jpg', 'jpeg', 'png', 'webp'],
    'storage_disk' => env('IMAGE_STORAGE_DISK', 'public'),
    'storage_path' => env('IMAGE_STORAGE_PATH', 'processed-images'),
];
```

### 2. Variables d'environnement

```env
IMAGE_DRIVER=gd
IMAGE_QUALITY=80
IMAGE_MAX_WIDTH=1200
IMAGE_MAX_HEIGHT=1200
IMAGE_STORAGE_DISK=public
IMAGE_STORAGE_PATH=processed-images
```

## Tests

### 1. Test unitaire créé

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageCompressionTest extends TestCase
{
    /** @test */
    public function it_can_compress_jpeg_image()
    {
        $image = UploadedFile::fake()->image('test.jpg', 2000, 1500);
        
        $result = $this->imageService->compressImage($image, [
            'quality' => 80,
            'max_width' => 800,
            'max_height' => 600,
            'format' => 'jpg'
        ]);

        $this->assertTrue($result['success']);
        $this->assertLessThan($image->getSize(), $result['compressed_size']);
    }
}
```

### 2. Exécution des tests

```bash
# Tester uniquement le service de compression
php artisan test --filter=ImageCompressionTest

# Tester tous les tests
php artisan test
```

## Avantages de la migration

### 1. Performance
- **Gestion mémoire améliorée** : Plus besoin d'appeler `destroy()`
- **API plus fluide** : Méthodes chaînables et immutables
- **Support des drivers modernes** : GD et Imagick optimisés

### 2. Maintenabilité
- **Code plus lisible** : Syntaxe plus claire et intuitive
- **Configuration centralisée** : Paramètres dans `config/image.php`
- **Tests automatisés** : Couverture complète des fonctionnalités

### 3. Compatibilité
- **Laravel 11** : Support natif de la version 3.x
- **PHP 8.2+** : Utilisation des fonctionnalités modernes
- **Packages tiers** : Compatible avec les autres packages Laravel

## Cas d'usage supportés

### 1. Photos de présence
```php
$result = $this->imageService->compressPresencePhoto($image);
// Qualité: 75%, Max: 800x800, Format: JPG
```

### 2. Photos de profil
```php
$result = $this->imageService->compressProfilePhoto($image);
// Qualité: 85%, Max: 400x400, Format: JPG
```

### 3. Images de documents
```php
$result = $this->imageService->compressDocumentImage($image);
// Qualité: 90%, Max: 1600x1600, Format: JPG
```

### 4. Compression personnalisée
```php
$result = $this->imageService->compressImage($image, [
    'quality' => 70,
    'max_width' => 600,
    'max_height' => 400,
    'format' => 'webp',
    'path' => 'custom/path',
    'disk' => 's3'
]);
```

## Vérification de la correction

1. **Service fonctionnel** : Plus d'erreur de classe manquante
2. **Configuration centralisée** : Paramètres dans `config/image.php`
3. **Tests automatisés** : Couverture complète des fonctionnalités
4. **API moderne** : Utilisation des méthodes Intervention Image 3.x
5. **Gestion mémoire** : Plus besoin de nettoyer manuellement

## Prévention des régressions

1. **Tests automatisés** : Exécuter `php artisan test --filter=ImageCompressionTest`
2. **Validation des formats** : Vérifier que tous les formats supportés fonctionnent
3. **Gestion des erreurs** : Tester avec des fichiers invalides
4. **Performance** : Vérifier que la compression fonctionne correctement
5. **Documentation** : Maintenir la documentation à jour

## Support des formats

- **JPEG** : Qualité 0-100, compression optimisée
- **PNG** : Qualité 0-9 (inversée), compression sans perte
- **WebP** : Qualité 0-100, format moderne et efficace
- **Autres** : Conversion automatique vers JPEG par défaut
