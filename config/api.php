<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */

    'version' => env('API_VERSION', 'v1'),
    'prefix' => env('API_PREFIX', 'api'),

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'enabled' => env('API_RATE_LIMIT_ENABLED', true),
        'max_attempts' => env('API_RATE_LIMIT_MAX', 60),
        'decay_minutes' => env('API_RATE_LIMIT_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    */

    'cors' => [
        'enabled' => env('API_CORS_ENABLED', true),
        'allowed_origins' => explode(',', env('API_CORS_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['*'],
        'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
        'max_age' => 86400,
        'supports_credentials' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Versioning
    |--------------------------------------------------------------------------
    */

    'versioning' => [
        'enabled' => env('API_VERSIONING_ENABLED', true),
        'default' => 'v1',
        'supported' => ['v1'],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Documentation
    |--------------------------------------------------------------------------
    */

    'documentation' => [
        'enabled' => env('API_DOCS_ENABLED', true),
        'path' => env('API_DOCS_PATH', '/api/docs'),
        'title' => env('APP_NAME', 'NeoCore') . ' API',
        'description' => 'API Documentation',
        'version' => '1.0.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Token Configuration
    |--------------------------------------------------------------------------
    */

    'tokens' => [
        'default_expiration' => env('API_TOKEN_EXPIRATION', 365), // days
        'prefix' => env('API_TOKEN_PREFIX', 'neocore'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */

    'webhooks' => [
        'enabled' => env('WEBHOOKS_ENABLED', true),
        'timeout' => env('WEBHOOKS_TIMEOUT', 30),
        'max_failures' => env('WEBHOOKS_MAX_FAILURES', 10),
    ],
];
