<?php

/**
 * View Configuration
 * 
 * Latte Template Engine settings
 */

return [
    // Debug mode
    'debug' => env('APP_DEBUG', false),
    
    // Views directory
    'views_dir' => BASE_PATH . '/resources/views',
    
    // Cache directory
    'cache_dir' => STORAGE_PATH . '/cache/views',
    
    // Auto-refresh templates in development
    'auto_refresh' => env('APP_DEBUG', false),
    
    // Global view variables
    'globals' => [
        'app_name' => env('APP_NAME', 'NeoCore'),
        'app_url' => env('APP_URL', 'http://localhost'),
    ],
];
