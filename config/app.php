<?php

return [
    'name' => 'NeoCore Application',
    'version' => '1.0.0',
    'environment' => 'development', // development, production
    'debug' => true,
    'timezone' => 'UTC',
    
    'url' => 'http://localhost:8000',
    
    // Error reporting
    'error_reporting' => E_ALL,
    'display_errors' => true,
    
    // Logging
    'log_path' => __DIR__ . '/../storage/logs',
    'log_level' => 'debug', // debug, info, warning, error
];
