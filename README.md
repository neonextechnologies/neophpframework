<div align="center">

# NeoPhp Framework

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.0.0-brightgreen.svg)](#changelog)

**A modern, full-stack PHP framework with modular architecture built for performance and developer experience**

*Powered by Cycle ORM and Latte Templates*

[Documentation](docs/README.md) • [Quick Start](#-quick-start) • [Features](#-features) • [Contributing](#-contributing)

</div>

---

## Features

### Core Features
- **Authentication & Authorization** - JWT, Session, RBAC, Permissions
- **Database** - Cycle ORM, PostgreSQL, MySQL, SQLite support
- **Template Engine** - Latte (Blade-like syntax, 2x faster)
- **Routing** - RESTful, Resource routes, Route groups
- **Security** - CSRF, XSS protection, Password hashing, Rate limiting

### Advanced Features
- **Storage & Media** - Local, S3, Image processing, CDN support
- **Caching** - Redis, Memcached, File, Array drivers
- **API** - RESTful API, JSON responses, API authentication
- **Logging & Monitoring** - PSR-3, Multiple channels, Error tracking
- **Localization** - Multi-language, Translation management
- **Task Scheduler** - Cron-like scheduling, Background jobs
- **SEO** - Meta tags, Sitemaps, Open Graph, Schema.org
- **CMS** - Page management, Content blocks, Menus
- **Broadcasting** - Pusher, Redis pub/sub, WebSockets
- **Testing** - HTTP testing, Database testing, Mocking

## Why NeoPhp Framework?

- **Performance First**: Built with Cycle ORM (2-3x faster than Eloquent)
- **Modern PHP**: PHP 8.3+, typed properties, enums, attributes
- **Feature Complete**: Everything you need out of the box
- **Modular Monolith**: Well-organized modules, easy to scale
- **Developer Friendly**: Intuitive APIs, comprehensive documentation
- **Clean Architecture**: Service-oriented, SOLID principles
- **Production Ready**: Battle-tested components, security first

## Project Structure

```
neophpframework/
├── app/                    # Application code
│   ├── Http/              # HTTP layer
│   │   ├── Controllers/   # Request handlers
│   │   ├── Middleware/    # HTTP middleware
│   │   └── Requests/      # Form requests
│   ├── Models/            # Database models
│   └── Services/          # Business logic
├── config/                # Configuration files
├── NeoPhp/               # Framework core
│   ├── src/              # Core source code
│   └── helpers/          # Helper functions
├── public/                # Web root
│   └── index.php         # Entry point
├── resources/             # Resources
│   ├── views/            # Latte templates
│   └── lang/             # Language files
├── routes/                # Route definitions
├── storage/               # Storage
│   ├── cache/            # Cache files
│   ├── logs/             # Log files
│   └── uploads/          # Uploaded files
└── tests/                 # Test suite
```

## Installation

### Requirements
- PHP 8.3 or higher
- Composer 2.0+
- MySQL 5.7+ / PostgreSQL 12+ / SQLite 3+
- PDO Extension
- JSON Extension

### Quick Install

```bash
# Clone repository
git clone https://github.com/yourusername/NeoPhp.git neophpframework
cd neophpframework

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure database in .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=NeoPhp
DB_USERNAME=root
DB_PASSWORD=

# Generate application key
php neo key:generate

# Run migrations
php neo migrate

# Start development server
php neo serve
```

Visit `http://localhost:8000` For detailed installation instructions, see [Installation Guide](docs/INSTALLATION.md).

## CLI Commands

### Quick Setup (Optional)

**Windows:** Add project directory to PATH, then use `neo` directly:
```cmd
neo make:controller UserController
neo serve
```

**Linux/Mac:** Create symlink or add to PATH:
```bash
sudo ln -s /path/to/project/neo /usr/local/bin/neo
# Or add to ~/.bashrc: export PATH="/path/to/project:$PATH"

neo make:controller UserController
neo serve
```

**Without PATH setup:** Use `php neo` instead:
```bash
php neo make:controller UserController
php neo serve
```

### Available Commands

The NeoPhp CLI provides powerful commands for development:

### Generators
```bash
neo make:controller UserController    # Create controller
neo make:model User                   # Create model
neo make:migration create_users_table # Create migration
neo make:middleware AuthMiddleware    # Create middleware
neo make:service UserService          # Create service
neo make:seeder UserSeeder           # Create seeder
neo make:factory UserFactory         # Create factory
neo make:command SendEmails          # Create CLI command
```

### Database
```bash
neo migrate                  # Run migrations
neo migrate:rollback         # Rollback last migration
neo migrate:reset            # Reset all migrations
neo migrate:refresh          # Reset and re-run migrations
neo migrate:status           # Show migration status
neo db:seed                  # Run database seeders
```

### Cache & Optimization
```bash
neo cache:clear              # Clear application cache
neo view:clear               # Clear compiled views
neo config:clear             # Clear config cache
neo route:clear              # Clear route cache
neo optimize                 # Optimize for production
```

### Queue & Scheduler
```bash
neo queue:work               # Start queue worker
neo queue:listen             # Listen to queue
neo queue:restart            # Restart queue workers
neo schedule:run             # Run scheduled tasks
neo schedule:list            # List scheduled tasks
```

### Development
```bash
neo serve                    # Start dev server (localhost:8000)
neo serve --port=3000        # Custom port
neo tinker                   # Interactive console
neo key:generate             # Generate APP_KEY
neo storage:link             # Create storage symlink
```

### Testing
```bash
neo test                     # Run tests
neo test --filter=UserTest   # Run specific test
neo test --coverage          # Run with coverage
```

## Quick Start

### Create Your First API Endpoint

```bash
# Create a controller
neo make:controller Api/UserController

# Create a model
neo make:model User
```

**Define routes** in `routes/api.php`:

```php
use NeoPhp\Router\Router;

$router = new Router();

$router->prefix('/api')->group(function($router) {
    $router->get('/users', [App\Http\Controllers\Api\UserController::class, 'index']);
    $router->post('/users', [App\Http\Controllers\Api\UserController::class, 'store']);
    $router->get('/users/{id}', [App\Http\Controllers\Api\UserController::class, 'show']);
});

return $router;
```

**Controller** (`app/Http/Controllers/Api/UserController.php`):

```php
namespace App\Http\Controllers\Api;

use App\Models\User;
use NeoPhp\Http\Request;
use NeoPhp\Http\Response;
use NeoPhp\Http\Controller;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::all();
        return response()->json($users);
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create($validated);
        
        return response()->json($user, 201);
    }

    public function show(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }
}
```

Visit `http://localhost:8000/api/users` ## � Documentation

### Getting Started
- [Installation Guide](docs/INSTALLATION.md)
- [Quick Start Guide](docs/getting-started/quick-start.md)
- [Directory Structure](docs/directory-structure.md)
- [Configuration](docs/configuration.md)

### Core Concepts
- [Routing](docs/basics/routing.md)
- [Controllers](docs/basics/controllers.md)
- [Database & ORM](docs/database/orm.md)
- [Views & Templates](docs/basics/views.md)
- [Authentication](docs/security/authentication.md)
- [Authorization](docs/security/authorization.md)

### Advanced Topics
- [Storage & Media](docs/advanced/storage.md)
- [Caching](docs/advanced/caching.md)
- [REST API](docs/advanced/api.md)
- [Logging](docs/advanced/logging.md)
- [Localization](docs/advanced/localization.md)
- [Task Scheduler](docs/SCHEDULER.md)
- [Soft Deletes](docs/SOFT_DELETES.md)
- [SEO Management](docs/advanced/seo.md)
- [CMS System](docs/advanced/cms.md)
- [Broadcasting](docs/advanced/broadcasting.md)
- [Testing](docs/testing/getting-started.md)

### Reference
- [Contributing Guidelines](CONTRIBUTING.md)
- [Security Policy](SECURITY.md)
- [Changelog](CHANGELOG.md)
- [Composer Packages](COMPOSER_PACKAGES.md)

## Core Features

### Routing

```php
use NeoPhp\Router\Router;

$router = new Router();

// Basic routes
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);

// Route parameters
$router->get('/posts/{id}', function($id) {
    return response()->json(['id' => $id]);
});

// Route groups with prefix
$router->prefix('/api/v1')->group(function($router) {
    $router->get('/posts', [PostController::class, 'index']);
    $router->get('/posts/{id}', [PostController::class, 'show']);
});

// Middleware
$router->middleware(['auth', 'admin'])->group(function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
});

// Named routes
$router->get('/profile', [ProfileController::class, 'show'])->name('profile');

// Resource routes (RESTful)
$router->resource('/posts', PostController::class);
```

### Controllers

```php
namespace App\Http\Controllers;

use App\Models\User;
use NeoPhp\Http\Controller;
use NeoPhp\Http\Request;
use NeoPhp\Http\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::paginate(15);
        return response()->json($users);
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => password_hash($validated['password'], PASSWORD_BCRYPT),
        ]);

        return response()->json($user, 201);
    }

    public function show(int $id): Response
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|min:3|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
        ]);

        $user->update($validated);
        return response()->json($user);
    }

    public function destroy(int $id): Response
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        return response()->json(['message' => 'User deleted successfully']);
    }
}
```

### Database with Cycle ORM

```php
namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\HasMany;
use NeoPhp\Database\Model;

#[Entity(table: 'users')]
class User extends Model
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string', unique: true)]
    public string $email;

    #[Column(type: 'string')]
    public string $password;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $email_verified_at = null;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $created_at;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $updated_at;

    #[HasMany(target: Post::class)]
    public array $posts = [];

    // Query methods
    public static function findByEmail(string $email): ?self
    {
        return self::query()->where('email', $email)->first();
    }

    public static function active(): array
    {
        return self::query()->where('status', 'active')->get();
    }
}
```

**Usage:**

```php
// Find by ID
$user = User::find(1);

// Find or fail (throws exception)
$user = User::findOrFail(1);

// Create
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_BCRYPT),
]);

// Update
$user->update(['name' => 'Jane Doe']);

// Delete
$user->delete();

// Soft delete (if using soft deletes trait)
$user->softDelete();

// Query builder
$users = User::query()
    ->where('status', 'active')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Pagination
$users = User::paginate(15);

// Relationships
$user = User::find(1);
$posts = $user->posts; // Load related posts
```

### Views with Latte Templates

**Layout** (`resources/views/layouts/app.latte`):

```latte
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{block title}NeoPhp Framework{/block}</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        {if $user}
            <a href="/dashboard">Dashboard</a>
            <a href="/logout">Logout</a>
        {else}
            <a href="/login">Login</a>
        {/if}
    </nav>

    <main>
        {block content}Default content{/block}
    </main>

    <footer>
        <p>&copy; {date('Y')} NeoPhp Framework</p>
    </footer>

    <script src="/js/app.js"></script>
</body>
</html>
```

**Page** (`resources/views/users/index.latte`):

```latte
{extends 'layouts/app.latte'}

{block title}Users - NeoPhp{/block}

{block content}
    <h1>Users</h1>

    {if count($users) > 0}
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {foreach $users as $user}
                    <tr>
                        <td>{$user->id}</td>
                        <td>{$user->name}</td>
                        <td>{$user->email}</td>
                        <td>{$user->created_at|date:'Y-m-d'}</td>
                        <td>
                            <a href="/users/{$user->id}">View</a>
                            <a href="/users/{$user->id}/edit">Edit</a>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>

        {* Pagination *}
        {if $users->hasPages()}
            <div class="pagination">
                {if $users->previousPageUrl()}
                    <a href="{$users->previousPageUrl()}">Previous</a>
                {/if}
                
                <span>Page {$users->currentPage()} of {$users->lastPage()}</span>
                
                {if $users->nextPageUrl()}
                    <a href="{$users->nextPageUrl()}">Next</a>
                {/if}
            </div>
        {/if}
    {else}
        <p>No users found.</p>
    {/if}
{/block}
```

**Controller:**

```php
public function index(Request $request): Response
{
    $users = User::query()
        ->orderBy('created_at', 'desc')
        ->paginate(15);
    
    return view('users/index', ['users' => $users]);
}
```

### Authentication

```php
use NeoPhp\Auth\Auth;

// Login
$user = User::findByEmail($email);
if ($user && password_verify($password, $user->password)) {
    Auth::login($user);
    return redirect('/dashboard');
}

// Logout
Auth::logout();

// Check authentication
if (Auth::check()) {
    // User is authenticated
}

// Get current user
$user = Auth::user();

// Middleware protection
$router->middleware(['auth'])->group(function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
});

// JWT Authentication (API)
use NeoPhp\Auth\JWT;

$token = JWT::encode(['user_id' => $user->id]);
$payload = JWT::decode($token);
```

### Storage & File Upload

```php
use NeoPhp\Storage\Storage;

// Upload file
$file = $request->file('avatar');
$path = Storage::disk('public')->put('avatars', $file);

// Store with custom name
$path = Storage::disk('s3')->putFileAs('avatars', $file, 'user-123.jpg');

// Get file URL
$url = Storage::disk('public')->url($path);

// Download file
return Storage::disk('public')->download($path);

// Delete file
Storage::disk('public')->delete($path);

// Check if file exists
if (Storage::disk('public')->exists($path)) {
    // File exists
}

// Image processing
use NeoPhp\Media\ImageProcessor;

$processor = new ImageProcessor();
$processor->load($file)
    ->resize(300, 300)
    ->fit('cover')
    ->save('public/thumbnails/image.jpg');
```

### Caching

```php
use NeoPhp\Cache\Cache;

// Store cache
Cache::put('key', 'value', 3600); // TTL in seconds

// Get cache
$value = Cache::get('key');

// Get with default
$value = Cache::get('key', 'default');

// Check existence
if (Cache::has('key')) {
    // Key exists
}

// Remember (get or store)
$users = Cache::remember('users.all', 3600, function() {
    return User::all();
});

// Forever (no expiration)
Cache::forever('settings', $settings);

// Forget
Cache::forget('key');

// Clear all
Cache::flush();

// Tags (Redis only)
Cache::tags(['users', 'posts'])->put('key', 'value', 3600);
Cache::tags(['users'])->flush(); // Clear all tagged items
```

### Task Scheduler

```php
// app/Console/Kernel.php
use NeoPhp\Scheduler\Scheduler;

class Kernel
{
    public function schedule(Scheduler $schedule): void
    {
        // Run every minute
        $schedule->command('emails:send')
            ->everyMinute();

        // Run hourly
        $schedule->command('reports:generate')
            ->hourly();

        // Run daily at specific time
        $schedule->command('backups:create')
            ->dailyAt('02:00');

        // Run weekly
        $schedule->command('cache:clear')
            ->weekly()
            ->mondays()
            ->at('03:00');

        // Run monthly
        $schedule->command('invoices:generate')
            ->monthly();

        // Custom cron
        $schedule->command('custom:task')
            ->cron('*/5 * * * *'); // Every 5 minutes
    }
}
```

Run scheduler: `php neo schedule:run`

Add to crontab: `* * * * * cd /path-to-project && php neo schedule:run >> /dev/null 2>&1`

### Queue System

```php
use NeoPhp\Queue\Queue;

// Dispatch job
Queue::push(SendEmailJob::class, [
    'email' => 'user@example.com',
    'subject' => 'Welcome',
]);

// Delayed job
Queue::later(60, SendEmailJob::class, $data); // Delay 60 seconds

// Job class
namespace App\Jobs;

class SendEmailJob
{
    public function handle(array $data): void
    {
        $email = $data['email'];
        // Send email logic
    }
}
```

Run worker: `php neo queue:work`

### Logging

```php
use NeoPhp\Logging\Log;

// Log levels
Log::emergency('System is down');
Log::alert('Action required');
Log::critical('Critical condition');
Log::error('Error occurred');
Log::warning('Warning message');
Log::notice('Normal but significant');
Log::info('Informational message');
Log::debug('Debug information');

// With context
Log::error('Payment failed', [
    'user_id' => $userId,
    'amount' => $amount,
    'error' => $exception->getMessage(),
]);

// Different channels
Log::channel('slack')->error('Production error');
Log::channel('database')->info('User action logged');
```

##  Configuration

NeoPhp uses environment variables and configuration files for settings.

### Environment Configuration

Copy `.env.example` to `.env` and configure:

```env
# Application
APP_NAME="NeoPhp Framework"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=NeoPhp
DB_USERNAME=root
DB_PASSWORD=

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Session
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=

# Storage
FILESYSTEM_DRIVER=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

# Broadcasting
BROADCAST_DRIVER=redis
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
```

### Configuration Files

All configuration files are in the `config/` directory:

- `app.php` - Application settings
- `database.php` - Database connections
- `cache.php` - Cache configuration
- `queue.php` - Queue configuration
- `session.php` - Session settings
- `mail.php` - Email configuration
- `storage.php` - File storage
- `broadcasting.php` - Real-time broadcasting

See [Configuration Guide](docs/configuration.md) for details.

## Testing

NeoPhp provides testing utilities for HTTP and database testing:

```php
namespace Tests\Feature;

use NeoPhp\Testing\TestCase;
use App\Models\User;

class UserApiTest extends TestCase
{
    public function testCanCreateUser()
    {
        $response = $this->post('/api/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function testCanGetUser()
    {
        $user = User::factory()->create();

        $response = $this->get('/api/users/' . $user->id);

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'created_at',
            ]);
    }

    public function testRequiresAuthentication()
    {
        $response = $this->get('/api/dashboard');

        $response->assertUnauthorized();
    }
}
```

Run tests: `php neo test`

## Deployment

### Production Optimization

```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache configuration
php neo config:cache

# Cache routes
php neo route:cache

# Optimize application
php neo optimize

# Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Web Server Configuration

**Apache** (`.htaccess` included):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

**Nginx**:
```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/neophpframework/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

See [Deployment Guide](docs/deployment/production.md) for detailed instructions.

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### Development Setup

```bash
# Fork and clone repository
git clone https://github.com/yourusername/NeoPhp.git
cd NeoPhp

# Install dependencies
composer install

# Create feature branch
git checkout -b feature/my-new-feature

# Make changes and commit
git commit -am 'Add some feature'

# Push to branch
git push origin feature/my-new-feature

# Create Pull Request
```

### Code Style

```bash
# Run code formatter
composer format

# Run code analysis
composer analyse

# Run tests
composer test
```

## License

NeoPhp Framework is open-sourced software licensed under the [MIT license](LICENSE).

## Acknowledgments

NeoPhp is built on top of excellent open-source packages:

- [Cycle ORM](https://cycle-orm.dev/) - Database ORM
- [Latte](https://latte.nette.org/) - Template Engine
- [PSR-3 Logger](https://www.php-fig.org/psr/psr-3/) - Logging Interface
- [PSR-7 HTTP Message](https://www.php-fig.org/psr/psr-7/) - HTTP Interfaces

## Support

- [Documentation](docs/README.md)
- [Issue Tracker](https://github.com/yourusername/NeoPhp/issues)
- [Discussions](https://github.com/yourusername/NeoPhp/discussions)
- Email: support@NeoPhp.dev

## Sponsors

Become a sponsor and get your logo here with a link to your website.

---

<div align="center">

**Built with by the NeoPhp Team**

[Star us on GitHub](https://github.com/yourusername/NeoPhp) | [Follow on Twitter](https://twitter.com/NeoPhp) | [Hire Us](https://NeoPhp.dev)

</div>
| CLI Tools | |
| Multi-Tenancy | |
| Migration System | |

## Contributing

We welcome contributions! Here's how you can help:

### Ways to Contribute

- **Report Bugs** - Help us identify and fix issues
- **Suggest Features** - Share your ideas for improvements
- **Improve Documentation** - Help make our docs better
- **Submit Code** - Fix bugs or implement features
- **Write Tests** - Improve test coverage

### Development Setup

```bash
# Fork and clone the repository
git clone https://github.com/yourusername/neophpframework.git
cd neophpframework

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure database in .env

# Run migrations
php neocore migrate

# Run tests
composer test
```

### Development Process

1. **Fork & Create Branch**: `git checkout -b feature/my-feature`
2. **Make Changes**: Write clean code following PSR-12
3. **Test**: Run `composer test` and ensure all tests pass
4. **Commit**: Use clear commit messages
5. **Push & PR**: Submit pull request with description

### Code Standards

- Follow PSR-12 coding style
- Use strict types: `declare(strict_types=1);`
- Type hint everything: parameters and return types
- Write tests for new features
- Keep functions small and focused
- Document complex logic

### Commit Message Format

```
type(scope): subject

feat: add user authentication
fix: resolve caching issue
docs: update installation guide
test: add user controller tests
```

## Security

### Reporting Security Issues

**Please do NOT report security vulnerabilities through public GitHub issues.**

Report security issues to: **security@neophp.dev**

### What to Include

- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Fix Release**: Depends on severity (critical: 1-7 days)

### Security Features

NeoPhp Framework includes:

- **JWT Authentication** - Secure token-based auth
- **RBAC** - Role-based access control
- **SQL Injection Protection** - Prepared statements with Cycle ORM
- **XSS Protection** - Template escaping with Latte
- **CSRF Protection** - Token validation middleware
- **Rate Limiting** - Built-in throttling
- **Password Hashing** - Bcrypt/Argon2 support

### Security Best Practices

```php
// Good - Use ORM (prevents SQL injection)
$user = User::where('email', $email)->first();

// Bad - Raw SQL without parameters
$user = DB::raw("SELECT * FROM users WHERE email = '$email'");

// Always validate input
$validated = $request->validate([
    'email' => 'required|email|max:255',
    'password' => 'required|min:8',
]);

// Use CSRF protection
$router->middleware(['csrf'])->group(function($router) {
    $router->post('/profile', [ProfileController::class, 'update']);
});
```

## � Code of Conduct

### Our Standards

- **Be Respectful** - Treat everyone with respect
- **Be Inclusive** - Welcome diverse perspectives
- **Be Constructive** - Focus on helpful feedback
- **Be Professional** - Keep discussions on topic

### Unacceptable Behavior

- Harassment or discrimination
- Trolling or insulting comments
- Publishing private information
- Other unprofessional conduct

Report issues to: conduct@neophp.dev

## Changelog

### Version 1.0.0 (December 2025)

**Initial Release**

- Core framework with routing, controllers, models
- Cycle ORM integration (MySQL, PostgreSQL, SQLite)
- Latte template engine
- Authentication & Authorization (JWT, Session, RBAC)
- Storage & Media management (Local, S3, CDN)
- Caching system (Redis, Memcached, File)
- REST API support
- Logging & Monitoring
- Localization
- Task Scheduler
- SEO & CMS features
- Broadcasting (Pusher, Redis)
- Testing utilities
- CLI commands

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

For security issues, please see [SECURITY.md](SECURITY.md).

## License

MIT License - see [LICENSE](LICENSE) file.

---

<div align="center">

**NeoPhp** - Simple. Explicit. Predictable.

Made with by developers who value clarity

</div>
