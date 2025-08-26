<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageCompressionSimpleTest extends TestCase
{
    protected ImageCompressionService $imageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageService = new ImageCompressionService();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_create_image_compression_service()
    {
        $this->assertInstanceOf(ImageCompressionService::class, $this->imageService);
    }

    /** @test */
    public function it_has_default_configuration()
    {
        // Test que le service peut être instancié sans erreur
        $this->assertInstanceOf(ImageCompressionService::class, $this->imageService);
        
        // Test que les méthodes publiques existent
        $this->assertTrue(method_exists($this->imageService, 'compressImage'));
        $this->assertTrue(method_exists($this->imageService, 'compressPresencePhoto'));
        $this->assertTrue(method_exists($this->imageService, 'compressProfilePhoto'));
        $this->assertTrue(method_exists($this->imageService, 'compressDocumentImage'));
    }

    /** @test */
    public function it_can_compress_batch_images()
    {
        $images = [
            UploadedFile::fake()->image('test1.jpg', 800, 600),
            UploadedFile::fake()->image('test2.png', 1200, 800)
        ];
        
        $result = $this->imageService->compressBatch($images);
        
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('global_stats', $result);
        $this->assertEquals(2, $result['global_stats']['total_images']);
    }

    /** @test */
    public function it_can_replace_images()
    {
        Storage::disk('public')->put('old.jpg', 'old content');
        Storage::disk('public')->put('new.jpg', 'new content');
        
        $result = $this->imageService->replaceImage('old.jpg', 'new.jpg');
        
        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('public')->exists('old.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('new.jpg'));
    }

    /** @test */
    public function it_can_cleanup_temp_images()
    {
        Storage::disk('public')->put('temp-images/test.jpg', 'temp content');
        
        $deletedCount = $this->imageService->cleanupTempImages();
        
        $this->assertIsInt($deletedCount);
    }
}
