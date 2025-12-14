<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SEO Configuration
    |--------------------------------------------------------------------------
    |
    | Default values for meta tags and SEO settings
    |
    */

    'defaults' => [
        'title' => env('APP_NAME', 'NeoCore Framework'),
        'title_separator' => ' | ',
        'description' => 'A modern PHP framework with advanced features',
        'keywords' => 'php, framework, neocore, modern',
        'author' => 'NeoCore',
        'robots' => 'index, follow',
        'canonical' => null,
        'image' => '/images/default-og-image.jpg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Open Graph Configuration
    |--------------------------------------------------------------------------
    |
    | Open Graph meta tags for social media sharing
    |
    */

    'opengraph' => [
        'enabled' => true,
        'type' => 'website',
        'site_name' => env('APP_NAME', 'NeoCore Framework'),
        'locale' => 'en_US',
        'image' => [
            'width' => 1200,
            'height' => 630,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Twitter Card Configuration
    |--------------------------------------------------------------------------
    |
    | Twitter Card meta tags
    |
    */

    'twitter' => [
        'enabled' => true,
        'card' => 'summary_large_image',
        'site' => '@neocore',
        'creator' => '@neocore',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for XML sitemap generation
    |
    */

    'sitemap' => [
        'enabled' => true,
        'cache_duration' => 3600, // 1 hour
        'max_urls' => 50000,
        'images' => true,
        'exclude_paths' => [
            '/admin/*',
            '/api/*',
            '/auth/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Robots.txt Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for robots.txt generation
    |
    */

    'robots' => [
        'user_agent' => '*',
        'allow' => [
            '/',
        ],
        'disallow' => [
            '/admin',
            '/api',
            '/auth',
        ],
        'sitemap' => '/sitemap.xml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema.org Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for JSON-LD structured data
    |
    */

    'schema' => [
        'enabled' => true,
        'organization' => [
            'name' => env('APP_NAME', 'NeoCore'),
            'url' => env('APP_URL', 'http://localhost'),
            'logo' => '/images/logo.png',
        ],
    ],
];
