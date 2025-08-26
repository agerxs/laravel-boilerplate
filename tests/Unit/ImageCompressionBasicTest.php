<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ImageCompressionService;

class ImageCompressionBasicTest extends TestCase
{
    protected ImageCompressionService $imageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageService = app(ImageCompressionService::class);
    }

    /** @test */
    public function it_can_create_image_compression_service()
    {
        $this->assertInstanceOf(ImageCompressionService::class, $this->imageService);
    }

    /** @test */
    public function it_has_required_public_methods()
    {
        $this->assertTrue(method_exists($this->imageService, 'compressImage'));
        $this->assertTrue(method_exists($this->imageService, 'compressPresencePhoto'));
        $this->assertTrue(method_exists($this->imageService, 'compressProfilePhoto'));
        $this->assertTrue(method_exists($this->imageService, 'compressDocumentImage'));
        $this->assertTrue(method_exists($this->imageService, 'compressBatch'));
        $this->assertTrue(method_exists($this->imageService, 'replaceImage'));
        $this->assertTrue(method_exists($this->imageService, 'cleanupTempImages'));
    }
}
