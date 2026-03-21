<?php

// ═══════════════════════════════════════════════════════════════════════════
// Performance Optimization Configuration
// ═══════════════════════════════════════════════════════════════════════════

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration for Performance
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'driver'  => env('CACHE_DRIVER', 'redis'),
        'ttl'     => 3600, // 1 hour default
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Query Optimization
    |--------------------------------------------------------------------------
    */

    'database' => [
        // Enable query logging only in development
        'log_queries' => env('APP_DEBUG', false),
        
        // Connection timeout (seconds)
        'connection_timeout' => 5,
        
        // Use connection pooling
        'pool' => [
            'min' => 5,
            'max' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Loading & Compression
    |--------------------------------------------------------------------------
    */

    'assets' => [
        // Enable gzip compression
        'gzip' => true,
        
        // Use CDN for static files (optional)
        'cdn_url' => env('CDN_URL', ''),
        
        // Cache busting with version
        'cache_bust_version' => env('CACHE_BUST_VERSION', '1.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Page Loading Optimization
    |--------------------------------------------------------------------------
    */

    'page_loading' => [
        // Pagination items per page
        'items_per_page' => 15,
        
        // Lazy load images
        'lazy_load_images' => true,
        
        // Enable infinite scroll for news
        'infinite_scroll' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Caching Headers
    |--------------------------------------------------------------------------
    */

    'http_cache' => [
        // Cache-Control max-age (seconds)
        'max_age' => 3600,
        
        // Browser cache for static assets (days)
        'static_cache_days' => 30,
        
        // Enable etag
        'etag' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Optimization
    |--------------------------------------------------------------------------
    */

    'query_optimization' => [
        // Use eager loading with relationships
        'eager_load' => true,
        
        // Chunk size for large datasets
        'chunk_size' => 1000,
        
        // Use select only needed columns
        'selective_columns' => true,
    ],
];
