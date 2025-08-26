<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImageCompressionTest extends TestCase
{
    use RefreshDatabase;

    protected ImageCompressionService $imageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageService = new ImageCompressionService();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_compress_jpeg_image()
    {
        // Créer un fichier image JPEG de test
        $image = UploadedFile::fake()->image('test.jpg', 2000, 1500);

        $result = $this->imageService->compressImage($image, [
            'quality' => 80,
            'max_width' => 800,
            'max_height' => 600,
            'format' => 'jpg'
        ]);

        $this->assertTrue($result['success']);
        $this->assertLessThan($image->getSize(), $result['compressed_size']);
        $this->assertEquals('jpg', $result['format']);
        $this->assertEquals(80, $result['quality']);
    }

    /** @test */
    public function it_can_compress_png_image()
    {
        // Créer un fichier image PNG de test
        $image = UploadedFile::fake()->image('test.png', 1600, 1200);

        $result = $this->imageService->compressImage($image, [
            'quality' => 90,
            'max_width' => 800,
            'max_height' => 600,
            'format' => 'png'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('png', $result['format']);
        $this->assertEquals(90, $result['quality']);
    }

    /** @test */
    public function it_can_compress_webp_image()
    {
        // Créer un fichier image de test
        $image = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result = $this->imageService->compressImage($image, [
            'quality' => 85,
            'max_width' => 600,
            'max_height' => 400,
            'format' => 'webp'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('webp', $result['format']);
        $this->assertEquals(85, $result['quality']);
    }

    /** @test */
    public function it_can_compress_presence_photo()
    {
        $image = UploadedFile::fake()->image('presence.jpg', 1600, 1200);

        $result = $this->imageService->compressPresencePhoto($image);

        $this->assertTrue($result['success']);
        $this->assertEquals('jpg', $result['format']);
        $this->assertEquals(75, $result['quality']);
        $this->assertLessThanOrEqual(800, $result['dimensions']['width']);
        $this->assertLessThanOrEqual(800, $result['dimensions']['height']);
    }

    /** @test */
    public function it_can_compress_profile_photo()
    {
        $image = UploadedFile::fake()->image('profile.jpg', 800, 600);

        $result = $this->imageService->compressProfilePhoto($image);

        $this->assertTrue($result['success']);
        $this->assertEquals('jpg', $result['format']);
        $this->assertEquals(85, $result['quality']);
        $this->assertLessThanOrEqual(400, $result['dimensions']['width']);
        $this->assertLessThanOrEqual(400, $result['dimensions']['height']);
    }

    /** @test */
    public function it_can_compress_document_image()
    {
        $image = UploadedFile::fake()->image('document.jpg', 2400, 1800);

        $result = $this->imageService->compressDocumentImage($image);

        $this->assertTrue($result['success']);
        $this->assertEquals('jpg', $result['format']);
        $this->assertEquals(90, $result['quality']);
        $this->assertLessThanOrEqual(1600, $result['dimensions']['width']);
        $this->assertLessThanOrEqual(1600, $result['dimensions']['height']);
    }

    /** @test */
    public function it_handles_invalid_image_file()
    {
        $invalidFile = UploadedFile::fake()->create('test.txt', 100);

        $result = $this->imageService->compressImage($invalidFile);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_preserves_aspect_ratio_when_resizing()
    {
        $image = UploadedFile::fake()->image('landscape.jpg', 2000, 1000);

        $result = $this->imageService->compressImage($image, [
            'max_width' => 800,
            'max_height' => 600
        ]);

        $this->assertTrue($result['success']);
        
        // L'image devrait conserver ses proportions
        $width = $result['dimensions']['width'];
        $height = $result['dimensions']['height'];
        
        $this->assertLessThanOrEqual(800, $width);
        $this->assertLessThanOrEqual(600, $height);
        
        // Vérifier que les proportions sont conservées (ratio ~2:1)
        $ratio = $width / $height;
        $this->assertEquals(2.0, $ratio, '', 0.1);
    }
}
