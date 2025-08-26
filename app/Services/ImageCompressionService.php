<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Exception;

class ImageCompressionService
{
    /**
     * Qualité de compression par défaut (0-100)
     */
    protected int $defaultQuality = 80;

    /**
     * Largeur maximale par défaut
     */
    protected int $defaultMaxWidth = 1200;

    /**
     * Hauteur maximale par défaut
     */
    protected int $defaultMaxHeight = 1200;

    /**
     * Formats supportés pour la compression
     */
    protected array $supportedFormats = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Compresse et optimise une image
     */
    public function compressImage(UploadedFile $image, array $options = []): array
    {
        try {
            // Vérifier que l'image est valide
            if (!$image->isValid()) {
                throw new Exception('Fichier image invalide');
            }

            // Récupérer les options de compression
            $quality = $options['quality'] ?? $this->defaultQuality;
            $maxWidth = $options['max_width'] ?? $this->defaultMaxWidth;
            $maxHeight = $options['max_height'] ?? $this->defaultMaxHeight;
            $format = $options['format'] ?? $this->getOptimalFormat($image);
            $disk = $options['disk'] ?? 'public';
            $path = $options['path'] ?? 'compressed-images';

            // Créer l'instance Intervention Image
            $img = Image::make($image->getRealPath());

            // Redimensionner l'image si nécessaire
            $img = $this->resizeImage($img, $maxWidth, $maxHeight);

            // Optimiser l'image selon le format
            $img = $this->optimizeImage($img, $format, $quality);

            // Générer un nom de fichier unique
            $filename = $this->generateFilename($image, $format);
            $fullPath = $path . '/' . $filename;

            // Sauvegarder l'image compressée
            $this->saveImage($img, $fullPath, $format, $quality, $disk);

            // Récupérer les informations sur l'image compressée
            $compressedInfo = $this->getImageInfo($img, $fullPath, $disk);

            // Nettoyer la mémoire
            $img->destroy();

            return [
                'success' => true,
                'original_path' => $image->getRealPath(),
                'compressed_path' => $fullPath,
                'original_size' => $image->getSize(),
                'compressed_size' => $compressedInfo['size'],
                'compression_ratio' => $this->calculateCompressionRatio($image->getSize(), $compressedInfo['size']),
                'dimensions' => $compressedInfo['dimensions'],
                'format' => $format,
                'quality' => $quality
            ];

        } catch (Exception $e) {
            Log::error('Erreur lors de la compression d\'image', [
                'file' => $image->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'original_path' => $image->getRealPath()
            ];
        }
    }

    /**
     * Redimensionne l'image en conservant les proportions
     */
    protected function resizeImage($img, int $maxWidth, int $maxHeight)
    {
        $width = $img->width();
        $height = $img->height();

        // Vérifier si le redimensionnement est nécessaire
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return $img;
        }

        // Redimensionner en conservant les proportions
        $img->resize($maxWidth, $maxHeight, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        return $img;
    }

    /**
     * Optimise l'image selon le format
     */
    protected function optimizeImage($img, string $format, int $quality)
    {
        switch (strtolower($format)) {
            case 'jpg':
            case 'jpeg':
                $img->encode('jpg', $quality);
                break;

            case 'png':
                // Pour PNG, utiliser une qualité différente (0-9, où 9 est la meilleure compression)
                $pngQuality = 9 - (($quality / 100) * 9);
                $img->encode('png', $pngQuality);
                break;

            case 'webp':
                $img->encode('webp', $quality);
                break;

            default:
                $img->encode('jpg', $quality);
                break;
        }

        return $img;
    }

    /**
     * Détermine le format optimal pour l'image
     */
    protected function getOptimalFormat(UploadedFile $image): string
    {
        $mimeType = $image->getMimeType();
        $extension = strtolower($image->getClientOriginalExtension());

        // Si c'est déjà un format supporté, le conserver
        if (in_array($extension, $this->supportedFormats)) {
            return $extension;
        }

        // Déterminer le format selon le type MIME
        switch ($mimeType) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/webp':
                return 'webp';
            default:
                return 'jpg'; // Format par défaut
        }
    }

    /**
     * Génère un nom de fichier unique
     */
    protected function generateFilename(UploadedFile $image, string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = uniqid();
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        
        // Nettoyer le nom original (supprimer les caractères spéciaux)
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        
        return "{$cleanName}_{$timestamp}_{$random}.{$format}";
    }

    /**
     * Sauvegarde l'image compressée
     */
    protected function saveImage($img, string $path, string $format, int $quality, string $disk)
    {
        $storage = Storage::disk($disk);
        
        // Créer le répertoire s'il n'existe pas
        $directory = dirname($path);
        if (!$storage->exists($directory)) {
            $storage->makeDirectory($directory);
        }

        // Sauvegarder l'image
        $storage->put($path, $img->stream($format, $quality));
    }

    /**
     * Récupère les informations sur l'image compressée
     */
    protected function getImageInfo($img, string $path, string $disk): array
    {
        $storage = Storage::disk($disk);
        $size = $storage->size($path);

        return [
            'size' => $size,
            'dimensions' => [
                'width' => $img->width(),
                'height' => $img->height()
            ]
        ];
    }

    /**
     * Calcule le ratio de compression
     */
    protected function calculateCompressionRatio(int $originalSize, int $compressedSize): float
    {
        if ($originalSize === 0) {
            return 0;
        }

        $ratio = (($originalSize - $compressedSize) / $originalSize) * 100;
        return round($ratio, 2);
    }

    /**
     * Compresse une image avec des paramètres spécifiques pour les photos de présence
     */
    public function compressPresencePhoto(UploadedFile $image): array
    {
        return $this->compressImage($image, [
            'quality' => 75,
            'max_width' => 800,
            'max_height' => 800,
            'format' => 'jpg',
            'path' => 'presence-photos/compressed',
            'disk' => 'public'
        ]);
    }

    /**
     * Compresse une image avec des paramètres pour les photos de profil
     */
    public function compressProfilePhoto(UploadedFile $image): array
    {
        return $this->compressImage($image, [
            'quality' => 85,
            'max_width' => 400,
            'max_height' => 400,
            'format' => 'jpg',
            'path' => 'profile-photos/compressed',
            'disk' => 'public'
        ]);
    }

    /**
     * Compresse une image avec des paramètres pour les documents
     */
    public function compressDocumentImage(UploadedFile $image): array
    {
        return $this->compressImage($image, [
            'quality' => 90,
            'max_width' => 1600,
            'max_height' => 1600,
            'format' => 'jpg',
            'path' => 'document-images/compressed',
            'disk' => 'public'
        ]);
    }

    /**
     * Compresse plusieurs images en lot
     */
    public function compressBatch(array $images, array $options = []): array
    {
        $results = [];
        $totalOriginalSize = 0;
        $totalCompressedSize = 0;

        foreach ($images as $key => $image) {
            $result = $this->compressImage($image, $options);
            $results[$key] = $result;

            if ($result['success']) {
                $totalOriginalSize += $result['original_size'];
                $totalCompressedSize += $result['compressed_size'];
            }
        }

        // Calculer les statistiques globales
        $globalStats = [
            'total_images' => count($images),
            'successful_compressions' => count(array_filter($results, fn($r) => $r['success'])),
            'failed_compressions' => count(array_filter($results, fn($r) => !$r['success'])),
            'total_original_size' => $totalOriginalSize,
            'total_compressed_size' => $totalCompressedSize,
            'global_compression_ratio' => $this->calculateCompressionRatio($totalOriginalSize, $totalCompressedSize),
            'space_saved' => $totalOriginalSize - $totalCompressedSize
        ];

        return [
            'results' => $results,
            'global_stats' => $globalStats
        ];
    }

    /**
     * Supprime l'ancienne image si la compression réussit
     */
    public function replaceImage(string $oldPath, string $newPath, string $disk = 'public'): bool
    {
        try {
            $storage = Storage::disk($disk);
            
            // Vérifier que la nouvelle image existe
            if (!$storage->exists($newPath)) {
                return false;
            }

            // Supprimer l'ancienne image
            if ($storage->exists($oldPath)) {
                $storage->delete($oldPath);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors du remplacement d\'image', [
                'old_path' => $oldPath,
                'new_path' => $newPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Nettoie les images temporaires
     */
    public function cleanupTempImages(string $disk = 'public'): int
    {
        try {
            $storage = Storage::disk($disk);
            $tempPath = 'temp-images';
            
            if (!$storage->exists($tempPath)) {
                return 0;
            }

            $files = $storage->files($tempPath);
            $deletedCount = 0;

            foreach ($files as $file) {
                // Supprimer les fichiers temporaires de plus de 24h
                $lastModified = $storage->lastModified($file);
                if (now()->diffInHours($lastModified) > 24) {
                    $storage->delete($file);
                    $deletedCount++;
                }
            }

            return $deletedCount;
        } catch (Exception $e) {
            Log::error('Erreur lors du nettoyage des images temporaires', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}

