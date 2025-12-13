<?php

/**
 * ORM Configuration
 * 
 * Cycle ORM settings
 */

return [
    'debug' => env('APP_DEBUG', false),
    
    // Use database config
    'default' => [
        'driver' => env('DB_CONNECTION', 'mysql'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'neocore'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    
    // Entity directories to scan
    'entity_paths' => [
        BASE_PATH . '/app/Entities',
    ],
    
    // Repository mappings
    'repositories' => [
        'App\\Entities\\User' => 'App\\Repositories\\UserRepository',
        'App\\Entities\\Product' => 'App\\Repositories\\ProductRepository',
    ],
];
