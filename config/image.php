<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "GD Library" and "Imagick" to process images
    | through PHP. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" is used.
    |
    | Supported: "gd", "imagick"
    |
    */

    'driver' => env('IMAGE_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Default Quality
    |--------------------------------------------------------------------------
    |
    | Default quality for JPEG images (0-100)
    |
    */

    'quality' => env('IMAGE_QUALITY', 80),

    /*
    |--------------------------------------------------------------------------
    | Default Max Width
    |--------------------------------------------------------------------------
    |
    | Default maximum width for resized images
    |
    */

    'max_width' => env('IMAGE_MAX_WIDTH', 1200),

    /*
    |--------------------------------------------------------------------------
    | Default Max Height
    |--------------------------------------------------------------------------
    |
    | Default maximum height for resized images
    |
    */

    'max_height' => env('IMAGE_MAX_HEIGHT', 1200),

    /*
    |--------------------------------------------------------------------------
    | Supported Formats
    |--------------------------------------------------------------------------
    |
    | List of supported image formats for processing
    |
    */

    'supported_formats' => [
        'jpg', 'jpeg', 'png', 'webp'
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | Default storage disk for processed images
    |
    */

    'storage_disk' => env('IMAGE_STORAGE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Storage Path
    |--------------------------------------------------------------------------
    |
    | Default storage path for processed images
    |
    */

    'storage_path' => env('IMAGE_STORAGE_PATH', 'processed-images'),
];
