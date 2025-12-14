<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Content Management System
    |
    */

    'page' => [
        'cache_enabled' => true,
        'cache_duration' => 3600, // 1 hour
        'auto_save' => true,
        'auto_save_interval' => 30, // seconds
        'revisions_enabled' => true,
        'max_revisions' => 10,
    ],

    'menu' => [
        'cache_enabled' => true,
        'cache_duration' => 3600,
        'max_depth' => 5,
    ],

    'widget' => [
        'cache_enabled' => true,
        'cache_duration' => 3600,
        'allowed_positions' => [
            'header',
            'sidebar',
            'footer',
            'content',
        ],
    ],

    'content_blocks' => [
        'allowed_types' => [
            'text',
            'html',
            'markdown',
            'image',
            'gallery',
            'video',
            'code',
            'quote',
            'accordion',
            'tabs',
        ],
    ],

    'breadcrumb' => [
        'separator' => ' / ',
        'home_text' => 'Home',
        'home_url' => '/',
    ],
];
