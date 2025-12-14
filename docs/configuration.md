# Configuration

NeoPhp's configuration system is simple and explicit. All configuration files are located in the `config/` directory.

## Configuration Files

### `config/app.php`

Application-level settings:

```php
<?php

return [
    'name' => env('APP_NAME', 'NeoPhp Application'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'en',
];
```

### `config/database.php`

Database connections:

```php
<?php

return [
    'default' => [
        'driver' => env('DB_CONNECTION', 'mysql'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'NeoPhp'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    
    // Additional connections
    'secondary' => [
        'driver' => 'mysql',
        'host' => 'secondary.example.com',
        // ...
    ],
];
```

### `config/queue.php`

Queue system configuration:

```php
<?php

return [
    'default' => env('QUEUE_DRIVER', 'file'),
    
    'drivers' => [
        'file' => [
            'path' => STORAGE_PATH . '/queue',
        ],
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
            'database' => 0,
        ],
    ],
    
    'retry_after' => 90,
    'max_retries' => 3,
];
```

### `config/modules.php`

Module system configuration:

```php
<?php

return [
    'enabled' => [
        // 'User',
        // 'Product',
    ],
    
    'path' => BASE_PATH . '/modules',
];
```

### `config/tenant.php`

Multi-tenancy configuration:

```php
<?php

return [
    'mode' => env('TENANT_MODE', 'subdomain'),
    // Options: 'subdomain', 'header', 'domain'
    
    'header_name' => env('TENANT_HEADER', 'X-Tenant-ID'),
    
    'domain_mapping' => [
        'tenant1.example.com' => 'tenant1',
        'tenant2.example.com' => 'tenant2',
    ],
    
    'database' => [
        'prefix' => 'tenant_',
        // tenant_tenant1, tenant_tenant2, etc.
    ],
];
```

### `config/routes.php`

Application routes:

```php
<?php

use NeoPhp\System\Core\Router;

return function(Router $router) {
    // Home
    $router->get('/', 'App\\Http\\Controllers\\HomeController@index');
    
    // API routes
    $router->prefix('/api')->group(function($router) {
        $router->get('/users', 'App\\Http\\Controllers\\UserController@index');
        $router->post('/users', 'App\\Http\\Controllers\\UserController@store');
    });
};
```

## Accessing Configuration

### Using Config Class

```php
use NeoPhp\System\Core\Config;

// Load entire config file
$appConfig = Config::load('app');

// Get specific value
$appName = Config::get('app.name');
$dbHost = Config::get('database.default.host');

// With default value
$timezone = Config::get('app.timezone', 'UTC');

// Nested values with dot notation
$queueDriver = Config::get('queue.drivers.file.path');
```

### Using env() Helper

```php
// Get from environment variable
$debug = env('APP_DEBUG', false);
$dbName = env('DB_DATABASE', 'NeoPhp');

// Type casting
$port = (int) env('DB_PORT', 3306);
```

## Environment Variables

### .env File

Create `.env` from `.env.example`:

```bash
cp .env.example .env
```

**Example `.env`:**

```env
# Application
APP_NAME="My NeoPhp App"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://myapp.com

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=NeoPhp_prod
DB_USERNAME=prod_user
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=redis_password

# Queue
QUEUE_DRIVER=redis

# Multi-tenancy
TENANT_MODE=subdomain
TENANT_HEADER=X-Tenant-ID

# Mail
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=noreply@myapp.com
MAIL_FROM_NAME="My App"

# JWT
JWT_SECRET=your-jwt-secret-key

# AWS
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

# Logging
LOG_LEVEL=info
```

### Security Note

**NEVER commit `.env` to version control!**

The `.gitignore` file already excludes it:

```gitignore
.env
.env.local
```

## Environment-Specific Configuration

### Development Environment

```env
APP_ENV=development
APP_DEBUG=true
DB_DATABASE=NeoPhp_dev
```

### Production Environment

```env
APP_ENV=production
APP_DEBUG=false
DB_DATABASE=NeoPhp_prod
```

### Testing Environment

```env
APP_ENV=testing
APP_DEBUG=true
DB_DATABASE=NeoPhp_test
```

## Custom Configuration Files

Create your own config files in `config/`:

**config/services.php:**

```php
<?php

return [
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],
    
    'aws' => [
        's3' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
        ],
    ],
];
```

**Usage:**

```php
$stripeKey = Config::get('services.stripe.key');
$s3Bucket = Config::get('services.aws.s3.bucket');
```

## Configuration Caching

For production, you can cache configuration (feature coming soon):

```bash
# Cache configuration
php neo config:cache

# Clear configuration cache
php neo config:clear
```

## Best Practices

### 1. Use Environment Variables for Secrets

```php
// ❌ Bad: Hardcoded secrets
return [
    'api_key' => 'hardcoded-secret-key',
];

// ✅ Good: From environment
return [
    'api_key' => env('API_KEY'),
];
```

### 2. Provide Sensible Defaults

```php
// ✅ Always provide defaults
return [
    'timeout' => env('HTTP_TIMEOUT', 30),
    'retries' => env('HTTP_RETRIES', 3),
];
```

### 3. Group Related Settings

```php
// ✅ Group by feature
return [
    'mail' => [
        'driver' => env('MAIL_DRIVER', 'smtp'),
        'host' => env('MAIL_HOST'),
        'port' => env('MAIL_PORT', 587),
    ],
    
    'sms' => [
        'driver' => env('SMS_DRIVER', 'twilio'),
        'from' => env('SMS_FROM'),
    ],
];
```

### 4. Document Your Configuration

```php
<?php

/**
 * Payment Gateway Configuration
 * 
 * Supported gateways: stripe, paypal, square
 */
return [
    'default' => env('PAYMENT_GATEWAY', 'stripe'),
    
    'gateways' => [
        'stripe' => [
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],
];
```

## Next Steps

- [Directory Structure](directory-structure.md) - Understand the file layout
- [Routing](basics/routing.md) - Define application routes
- [Database](database/getting-started.md) - Configure database connections
