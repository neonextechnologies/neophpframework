# Directory Structure

Understanding NeoPhp's directory structure will help you navigate and organize your application effectively.

## Root Directory

```
NeoPhp/
├── app/                # Your application code
├── config/             # Configuration files
├── docs/               # Documentation
├── modules/            # Isolated modules
├── public/             # Web root (document root)
├── storage/            # Logs, cache, sessions, queue
├── system/             # Framework core (don't modify)
├── tests/              # Test files
├── .env                # Environment variables (not in git)
├── .env.example        # Environment template
├── .gitignore          # Git ignore rules
├── composer.json       # Composer configuration
├── NeoPhp             # CLI tool
└── README.md           # Project readme
```

## The `app/` Directory

Your application code lives here:

```
app/
├── Http/
│   ├── Controllers/    # HTTP controllers
│   ├── Middleware/     # HTTP middleware
│   ├── Requests/       # Request validation classes
│   └── Responses/      # Response formatters
├── Models/             # Data models
├── Services/           # Business logic layer
├── Jobs/               # Queue job handlers
├── Libraries/          # Custom libraries
└── Policies/           # Authorization policies
```

### Controllers

Handle HTTP requests and return responses:

```php
// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use NeoPhp\System\Core\Controller;

class UserController extends Controller
{
    public function index() { }
}
```

### Middleware

Process requests before/after controllers:

```php
// app/Http/Middleware/AuthMiddleware.php
namespace App\Http\Middleware;

class AuthMiddleware
{
    public function handle($request, $response, $next) { }
}
```

### Models

Database interaction:

```php
// app/Models/User.php
namespace App\Models;

use NeoPhp\System\Core\Model;

class User extends Model
{
    protected string $table = 'users';
}
```

### Services

Business logic:

```php
// app/Services/UserService.php
namespace App\Services;

class UserService
{
    public function register(array $data) { }
}
```

### Jobs

Background tasks:

```php
// app/Jobs/SendWelcomeEmailJob.php
namespace App\Jobs;

class SendWelcomeEmailJob
{
    public function handle(array $data) { }
}
```

## The `config/` Directory

Configuration files:

```
config/
├── app.php             # Application settings
├── database.php        # Database connections
├── queue.php           # Queue configuration
├── modules.php         # Module settings
├── tenant.php          # Multi-tenancy config
└── routes.php          # Application routes
```

Each file returns a PHP array:

```php
// config/app.php
<?php
return [
    'name' => env('APP_NAME', 'NeoPhp'),
    'debug' => env('APP_DEBUG', false),
];
```

## The `modules/` Directory

Isolated, self-contained modules:

```
modules/
└── user/
    ├── Config/
    │   └── module.php      # Module configuration
    ├── Http/
    │   └── Controllers/    # Module controllers
    ├── Models/             # Module models
    ├── Services/           # Module services
    ├── Migrations/         # Module migrations
    ├── Routes/
    │   ├── api.php         # API routes
    │   └── web.php         # Web routes
    └── Resources/          # Views, assets
```

**Module configuration:**

```php
// modules/user/Config/module.php
return [
    'name' => 'User',
    'version' => '1.0.0',
    'routes' => [
        'api' => 'Routes/api.php',
    ],
];
```

## The `public/` Directory

Web server document root:

```
public/
├── .htaccess           # Apache rewrite rules
├── index.php           # Application entry point
├── favicon.ico         # Favicon
└── robots.txt          # Robots file
```

**index.php** - Application bootstrap:

```php
<?php
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/system/Core/Autoloader.php';
// ... bootstrap code
```

**Point your web server here!**

## The `storage/` Directory

Application storage (must be writable):

```
storage/
├── cache/              # Application cache
│   └── .gitkeep
├── logs/               # Log files
│   └── app.log
├── sessions/           # Session files
│   └── .gitkeep
├── queue/              # Queue jobs
│   ├── pending/
│   ├── processing/
│   └── failed/
├── migrations/         # Migration files
│   └── 001_create_users_table.php
└── reports/            # Generated reports
    └── .gitkeep
```

**Set permissions:**

```bash
chmod -R 755 storage/
```

## The `system/` Directory

Framework core files (don't modify):

```
system/
├── Core/               # Core classes
│   ├── Autoloader.php  # PSR-4 autoloader
│   ├── Router.php      # HTTP router
│   ├── Request.php     # Request wrapper
│   ├── Response.php    # Response wrapper
│   ├── Controller.php  # Base controller
│   ├── Model.php       # Base model
│   ├── Database.php    # Database manager
│   ├── Config.php      # Config loader
│   ├── EventBus.php    # Event system
│   ├── Queue.php       # Queue system
│   ├── Worker.php      # Queue worker
│   ├── Migration.php   # Migration base
│   ├── ModuleLoader.php # Module loader
│   └── TenantManager.php # Multi-tenancy
├── CLI/                # Command-line interface
│   ├── Console.php     # CLI runner
│   └── Commands/       # CLI commands
│       ├── Command.php
│       ├── MakeController.php
│       ├── MakeModel.php
│       ├── Migrate.php
│       └── ...
└── Helpers/            # Helper functions
    └── helpers.php
```

## The `tests/` Directory

Application tests:

```
tests/
├── bootstrap.php       # Test bootstrap
├── Unit/               # Unit tests
│   ├── RouterTest.php
│   └── ConfigTest.php
└── Feature/            # Feature tests
    └── HomePageTest.php
```

## Namespace Structure

NeoPhp uses PSR-4 autoloading:

```php
// Namespace → Directory mapping
NeoPhp\System\Core\Router      → system/Core/Router.php
App\Http\Controllers\UserController → app/Http/Controllers/UserController.php
Modules\User\Models\User        → modules/user/Models/User.php
```

**Autoloader configuration:**

```php
// composer.json
{
    "autoload": {
        "psr-4": {
            "NeoPhp\\System\\": "system/",
            "App\\": "app/",
            "Modules\\": "modules/"
        }
    }
}
```

## Creating New Directories

### Custom Libraries

```
app/Libraries/
└── PaymentGateway/
    ├── StripeGateway.php
    └── PayPalGateway.php
```

### Custom Helpers

```
app/Helpers/
└── custom_helpers.php
```

Load in `public/index.php`:

```php
require BASE_PATH . '/app/Helpers/custom_helpers.php';
```

### Resources (Views, Assets)

```
resources/
├── views/
│   ├── emails/
│   └── pdfs/
└── assets/
    ├── css/
    ├── js/
    └── images/
```

## Best Practices

### 1. Keep Controllers Thin

Put business logic in Services, not Controllers.

### 2. Use Services for Business Logic

```
app/Services/
├── UserService.php
├── OrderService.php
└── PaymentService.php
```

### 3. Organize by Feature

For large apps, consider feature-based organization:

```
app/Features/
├── User/
│   ├── UserController.php
│   ├── UserService.php
│   └── UserModel.php
└── Order/
    ├── OrderController.php
    ├── OrderService.php
    └── OrderModel.php
```

### 4. Separate API and Web

```
app/Http/Controllers/
├── Api/
│   └── UserController.php
└── Web/
    └── UserController.php
```

### 5. Use Modules for Major Features

For large, isolated features, create modules:

```bash
php neo make:module Billing
php neo make:module Reporting
```

## Next Steps

- [Routing](basics/routing.md) - Define application routes
- [Controllers](basics/controllers.md) - Create controllers
- [Models](database/models.md) - Work with data
