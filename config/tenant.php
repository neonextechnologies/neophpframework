<?php

return [
    // Tenant detection method: 'subdomain', 'header', 'domain'
    'detection_method' => 'subdomain',

    // Tenant header name (used when detection_method is 'header')
    'tenant_header' => 'X-Tenant-ID',

    // Domain mapping (used when detection_method is 'domain')
    'domain_map' => [
        // 'tenant1.com' => 'tenant1',
        // 'tenant2.com' => 'tenant2',
    ],

    // Tenant database configurations
    'tenants' => [
        // 'tenant1' => [
        //     'driver' => 'mysql',
        //     'host' => 'localhost',
        //     'port' => 3306,
        //     'database' => 'tenant1_db',
        //     'username' => 'root',
        //     'password' => '',
        //     'charset' => 'utf8mb4',
        // ],
    ],
];
