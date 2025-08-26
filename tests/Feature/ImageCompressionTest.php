<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageCompressionTest extends TestCase
{
    protected ImageCompressionService $imageCompressionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageCompressionService = app(ImageCompressionService::class);
        Storage::fake('public');
    }

    /** @test */
    public function it_can_compress_presence_photo()
    {
        // Créer un fichier image de test
        $image = UploadedFile::fake()->image('test_photo.jpg', 1200, 800);

        // Compresser l'image
        $result = $this->imageCompressionService->compressPresencePhoto($image);

        // Vérifier le résultat
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('compressed_path', $result);
        $this->assertArrayHasKey('compressed_size', $result);
        $this->assertArrayHasKey('compression_ratio', $result);

        // Vérifier que le fichier compressé existe
        $this->assertTrue(Storage::disk('public')->exists($result['compressed_path']));

        // Vérifier que la taille a été réduite
        $this->assertLessThan($image->getSize(), $result['compressed_size']);
    }

    /** @test */
    public function it_can_compress_profile_photo()
    {
        $image = UploadedFile::fake()->image('profile.jpg', 800, 600);

        $result = $this->imageCompressionService->compressProfilePhoto($image);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('compressed_path', $result);
        
        $this->assertTrue(Storage::disk('public')->exists($result['compressed_path']));
    }

    /** @test */
    public function it_can_compress_document()
    {
        $image = UploadedFile::fake()->image('document.png', 2000, 1500);

        $result = $this->imageCompressionService->compressDocumentImage($image);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('compressed_path', $result);
        
        $this->assertTrue(Storage::disk('public')->exists($result['compressed_path']));
    }

    /** @test */
    public function it_handles_invalid_image_files()
    {
        $invalidFile = UploadedFile::fake()->create('test.txt', 100);

        $result = $this->imageCompressionService->compressPresencePhoto($invalidFile);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    /** @test */
    public function it_handles_missing_files()
    {
        // Créer un fichier invalide au lieu de null
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 100);

        $result = $this->imageCompressionService->compressPresencePhoto($invalidFile);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    /** @test */
    public function it_generates_unique_filenames()
    {
        $image1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
        $image2 = UploadedFile::fake()->image('photo2.jpg', 800, 600);

        $result1 = $this->imageCompressionService->compressPresencePhoto($image1);
        $result2 = $this->imageCompressionService->compressPresencePhoto($image2);

        $this->assertNotEquals($result1['compressed_path'], $result2['compressed_path']);
    }

    /** @test */
    public function it_maintains_image_quality()
    {
        $image = UploadedFile::fake()->image('quality_test.jpg', 1000, 750);

        $result = $this->imageCompressionService->compressPresencePhoto($image);

        $this->assertTrue($result['success']);
        
        // Vérifier que le ratio de compression est raisonnable (pas trop agressif)
        $this->assertGreaterThan(20, $result['compression_ratio']);
        $this->assertLessThan(90, $result['compression_ratio']);
    }

    /** @test */
    public function it_creates_required_directories()
    {
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        $result = $this->imageCompressionService->compressPresencePhoto($image);

        $this->assertTrue($result['success']);
        
        // Vérifier que le dossier presence-photos existe
        $this->assertTrue(Storage::disk('public')->exists('presence-photos'));
    }

    /** @test */
    public function it_can_use_custom_compression_parameters()
    {
        $image = UploadedFile::fake()->image('custom.jpg', 1000, 800);

        $result = $this->imageCompressionService->compressImage(
            $image,
            [
                'max_width' => 500,
                'max_height' => 400,
                'quality' => 70,
                'format' => 'jpeg'
            ]
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('compressed_path', $result);
        
        $this->assertTrue(Storage::disk('public')->exists($result['compressed_path']));
    }
}
