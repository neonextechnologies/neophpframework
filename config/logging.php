<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | Supported: "single", "daily", "database", "stack"
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'database'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/neocore.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/neocore.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'logs',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'NeoCore Logger',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'email' => [
            'driver' => 'email',
            'to' => env('LOG_EMAIL_TO'),
            'subject' => 'Application Error',
            'level' => 'error',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Level
    |--------------------------------------------------------------------------
    |
    | Available levels: emergency, alert, critical, error, warning, notice, info, debug
    |
    */

    'level' => env('LOG_LEVEL', 'debug'),

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'enabled' => env('PERFORMANCE_MONITORING', true),
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000), // ms
        'slow_request_threshold' => env('SLOW_REQUEST_THRESHOLD', 2000), // ms
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Tracking
    |--------------------------------------------------------------------------
    */

    'error_tracking' => [
        'enabled' => env('ERROR_TRACKING', true),
        'ignore_exceptions' => [
            // Add exception classes to ignore
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */

    'audit' => [
        'enabled' => env('AUDIT_LOGGING', true),
        'events' => [
            'user.login',
            'user.logout',
            'user.created',
            'user.updated',
            'user.deleted',
        ],
    ],
];
