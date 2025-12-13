<?php

return [
    // Queue driver: 'file' or 'redis'
    'driver' => 'file',

    // File driver configuration
    'file' => [
        'path' => __DIR__ . '/../storage/queue',
    ],

    // Redis driver configuration
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
    ],

    // Worker configuration
    'worker' => [
        'max_attempts' => 3,
        'sleep' => 3, // seconds
        'timeout' => 60, // seconds
    ],
];
