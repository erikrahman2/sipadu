<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OCR Service Configuration
    |--------------------------------------------------------------------------
    */

    'service_url'    => env('OCR_SERVICE_URL', 'http://localhost:5001'),
    'secret_key'     => env('OCR_SECRET_KEY', ''),
    'fallback_secret_key' => env('OCR_FALLBACK_SECRET_KEY', 'change_me'),

    // IMPORTANT: timeout harus lebih besar dari waktu pemrosesan OCR (12 variants x 5 PSMs = 60 kombinasi)
    // Untuk gambar berkualitas rendah, proses bisa memakan waktu lama
    'timeout'        => env('OCR_TIMEOUT', 180),

    // fast_mode=true: proses OCR synchronously (memblokir request, bisa timeout)
    // fast_mode=false: proses async via queue (direkomendasikan untuk production)
    'fast_mode'      => env('OCR_FAST_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Accepted MIME types
    |--------------------------------------------------------------------------
    */

    'accepted_mimes' => [
        'image/jpeg',
        'image/png',
        'image/tiff',
        'application/pdf',
    ],

    'max_file_size_mb' => 10,

    /*
    |--------------------------------------------------------------------------
    | Preprocessing options
    |--------------------------------------------------------------------------
    */

    'preprocessing' => [
        'grayscale'     => true,
        'binarize'      => true,
        'denoise'       => true,
        'deskew'        => true,
        'resize_dpi'    => 300,
        'crop_roi'      => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document-type-specific preprocessing profiles
    |--------------------------------------------------------------------------
    */

    'document_profiles' => [
        'KTP_SUAMI' => [
            'grayscale'     => true,
            'binarize'      => true,
            'denoise'       => true,
            'deskew'        => true,
            'resize_dpi'    => 300,
            'contrast_boost' => 0.0,  // No  extra boost
            'adaptive_denoise_strength' => 10,
            'enable_variants' => true,
        ],
        'KTP_ISTRI' => [
            'grayscale'     => true,
            'binarize'      => true,
            'denoise'       => true,
            'deskew'        => true,
            'resize_dpi'    => 300,
            'contrast_boost' => 1.2,  // Boost contrast for spouse cards
            'adaptive_denoise_strength' => 12,  // Stronger denoise
            'enable_variants' => true,
            'extra_upscale' => 1.5,  // Additional upscaling for small text
            'bilateral_filter' => true,  // Better edge preservation
        ],
        'KTP' => [
            'grayscale'     => true,
            'binarize'      => true,
            'denoise'       => true,
            'deskew'        => true,
            'resize_dpi'    => 300,
            'contrast_boost' => 0.0,
            'adaptive_denoise_strength' => 10,
            'enable_variants' => true,
        ],
        'default' => [
            'grayscale'     => true,
            'binarize'      => true,
            'denoise'       => true,
            'deskew'        => true,
            'resize_dpi'    => 300,
            'contrast_boost' => 0.0,
            'adaptive_denoise_strength' => 10,
            'enable_variants' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Confidence thresholds
    |--------------------------------------------------------------------------
    */

    'confidence' => [
        'nik'   => 0.80,  // Lowered from 0.85 for better success rate
        'kk'    => 0.80,
        'nama'  => 0.70,  // Lowered from 0.80
        'default' => 0.65,  // Lowered from 0.75
    ],

    /*
    |--------------------------------------------------------------------------
    | Field regex patterns
    |--------------------------------------------------------------------------
    */

    'patterns' => [
        'nik'   => '/^\d{16}$/',
        'kk'    => '/^\d{16}$/',
        'tgl_lahir' => '/^\d{2}-\d{2}-\d{4}$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry configuration
    |--------------------------------------------------------------------------
    */

    'retry' => [
        'max_attempts' => 4,  // Increased from 3
        'backoff_seconds' => [5, 15, 30, 60],  // Faster initial retry
    ],
];
