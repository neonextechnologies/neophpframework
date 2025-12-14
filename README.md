<div align="center">

# NeoPhp Framework

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.0.0-brightgreen.svg)](CHANGELOG.md)

**A modern, full-stack PHP framework with modular architecture built for performance and developer experience**

*Powered by Cycle ORM and Latte Templates*

[Documentation](docs/introduction.md) • [Quick Start](#quick-start) • [Features](#features) • [Contributing](CONTRIBUTING.md)

</div>

---

## Features

### **High Performance**
Built with Cycle ORM (2-3x faster than Eloquent) and optimized for production workloads. Compiled templates and aggressive caching ensure your application runs at peak performance.

### **Modular Architecture**
Self-contained modules with dependency injection. Organize your code into isolated, testable modules that can scale independently.

### **Built-in Security**
JWT authentication and RBAC authorization out of the box. CSRF protection, XSS prevention, secure password hashing, and rate limiting included.

### **Database Integration**
Cycle ORM with PostgreSQL, MySQL, and SQLite support. Type-safe entities, repository pattern, eager/lazy loading, and powerful query builder.

### **Frontend Support**
Latte template engine with Blade-like syntax (2x faster). Auto-escaping, template inheritance, custom filters, and asset management.

### **Advanced Features**
WebSockets, GraphQL, caching (Redis/Memcached), background queues, real-time broadcasting, and event system.

---

## Quick Start

\\\ash
# Clone repository
git clone https://github.com/yourusername/neophpframework.git
cd neophpframework

# Install dependencies
composer install

# Configure environment
cp .env.example .env
nano .env

# Generate application key
neo key:generate

# Run migrations
neo migrate

# Start development server
neo serve
\\\

Your app is now running on **http://localhost:8000** with database, auth, and routing configured!

---

## Documentation

### INTRODUCTION
- [Welcome to NeoPhp Framework](docs/introduction.md)
- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/configuration.md)
- [Directory Structure](docs/directory-structure.md)

### GETTING STARTED
- [Quick Start Guide](docs/getting-started/quick-start.md)
- [Your First Application](docs/getting-started/quick-start.md#your-first-application)

### CORE CONCEPTS

#### The Basics
- [Routing](docs/basics/routing.md) - RESTful routes, resource routes, route groups
- [Controllers](docs/basics/controllers.md) - Request handling, dependency injection
- [Requests](docs/basics/requests.md) - Input validation, file uploads
- [Responses](docs/basics/responses.md) - JSON, redirects, downloads
- [Views](docs/basics/views.md) - Latte templates, layouts, components
- [Middleware](docs/basics/middleware.md) - HTTP filtering, authentication
- [Validation](docs/basics/validation.md) - Rules, custom validators
- [Forms](docs/basics/forms.md) - CSRF protection, form builders

#### Database & ORM
- [Getting Started](docs/database/getting-started.md) - Database setup
- [Cycle ORM](docs/database/orm.md) - Entity mapping, repositories
- [Models](docs/database/models.md) - Defining entities
- [Query Builder](docs/database/query-builder.md) - Building queries
- [Migrations](docs/database/migrations.md) - Schema management
- [Seeding](docs/database/seeding.md) - Test data
- [Relationships](docs/database/relationships.md) - One-to-many, many-to-many
- [Pagination](docs/database/pagination.md) - Result pagination

#### Security
- [Authentication](docs/security/authentication.md) - JWT, session auth
- [Authorization](docs/security/authorization.md) - Gates, policies
- [RBAC](docs/security/rbac.md) - Role-based access control
- [Permissions](docs/security/permissions.md) - Fine-grained permissions
- [JWT](docs/security/jwt.md) - Token management
- [CSRF Protection](docs/security/csrf.md) - Token validation
- [Password Hashing](docs/security/passwords.md) - Bcrypt, Argon2
- [Rate Limiting](docs/security/rate-limiting.md) - Throttling
- [Best Practices](docs/security/best-practices.md) - Security guidelines

### ADVANCED FEATURES

#### Storage & Files
- [Storage](docs/advanced/storage.md) - Local, S3, CDN
- File uploads and processing
- Image manipulation
- CDN integration

#### Caching
- [Caching](docs/advanced/caching.md) - Redis, Memcached, File
- Cache strategies
- Cache tags
- Performance optimization

#### API Development
- [REST API](docs/advanced/api.md) - RESTful APIs
- JSON resources
- API authentication
- Rate limiting
- Versioning

#### Background Processing
- [Queue System](docs/advanced/queue.md) - Background jobs
- [Task Scheduler](docs/SCHEDULER.md) - Cron-like scheduling
- Workers and supervisors
- Job batching

#### Content Management
- [CMS](docs/advanced/cms.md) - Page management
- [SEO](docs/advanced/seo.md) - Meta tags, sitemaps
- Content blocks
- Media library

#### Internationalization
- [Localization](docs/advanced/localization.md) - Multi-language
- Translation management
- Language switching

#### Logging & Monitoring
- [Logging](docs/advanced/logging.md) - PSR-3 logging
- [Error Tracking](docs/advanced/error-tracking.md) - Sentry integration
- Multiple channels
- Custom handlers

#### Developer Tools
- [Collections](docs/advanced/collections.md) - Array manipulation
- [Helpers](docs/advanced/helpers.md) - Utility functions
- [Events](docs/advanced/events.md) - Event system
- [Broadcasting](docs/advanced/broadcasting.md) - WebSockets, Pusher

### TESTING
- [Getting Started](docs/testing/getting-started.md) - PHPUnit setup
- [HTTP Tests](docs/testing/http-tests.md) - Testing APIs
- [Database Testing](docs/testing/database.md) - Factories, seeders
- [Mocking](docs/testing/mocking.md) - Test doubles
- [Assertions](docs/testing/assertions.md) - Custom assertions

### REFERENCE
- [CLI Commands](#cli-commands) - All available commands
- [Contributing](CONTRIBUTING.md) - How to contribute
- [Security Policy](SECURITY.md) - Reporting vulnerabilities
- [Changelog](CHANGELOG.md) - Version history

---

## CLI Commands

NeoPhp provides a powerful CLI for development:

### Generators
\\\ash
neo make:controller UserController    # Create controller
neo make:model User                   # Create model/entity
neo make:migration create_users       # Create migration
neo make:middleware Auth              # Create middleware
neo make:service UserService          # Create service class
neo make:seeder UserSeeder           # Create database seeder
neo make:factory UserFactory         # Create model factory
\\\

### Database
\\\ash
neo migrate                  # Run migrations
neo migrate:rollback         # Rollback last batch
neo migrate:reset            # Reset all migrations
neo migrate:refresh          # Reset and re-run all
neo db:seed                  # Run seeders
\\\

### Development
\\\ash
neo serve                    # Start dev server (localhost:8000)
neo serve --port=3000        # Custom port
neo tinker                   # Interactive console
neo key:generate             # Generate APP_KEY
\\\

### Cache & Optimization
\\\ash
neo cache:clear              # Clear application cache
neo view:clear               # Clear compiled views
neo config:clear             # Clear config cache
neo route:clear              # Clear route cache
neo optimize                 # Optimize for production
\\\

### Queue & Scheduler
\\\ash
neo queue:work               # Start queue worker
neo queue:listen             # Listen to queue
neo schedule:run             # Run scheduled tasks
neo schedule:list            # List all scheduled tasks
\\\

### Testing
\\\ash
neo test                     # Run all tests
neo test --filter=UserTest   # Run specific test
neo test --coverage          # Generate coverage report
\\\

---

## Example Usage

### Basic Routing

\\\php
use NeoPhp\Router\Router;
use App\Http\Controllers\UserController;

\ = new Router();

// Basic routes
\->get('/', fn() => view('welcome'));
\->get('/users', [UserController::class, 'index']);
\->post('/users', [UserController::class, 'store']);

// Resource routes
\->resource('/posts', PostController::class);

// Route groups with middleware
\->middleware(['auth'])->group(function(\) {
    \->get('/dashboard', [DashboardController::class, 'index']);
    \->get('/profile', [ProfileController::class, 'show']);
});
\\\

### Controllers with Dependency Injection

\\\php
namespace App\Http\Controllers;

use App\Services\UserService;
use App\Repositories\UserRepository;
use NeoPhp\Http\Request;
use NeoPhp\Http\Response;

class UserController
{
    public function __construct(
        private UserRepository \,
        private UserService \
    ) {}
    
    public function index(Request \): Response
    {
        \ = \->users->paginate(15);
        return view('users.index', compact('users'));
    }
    
    public function store(Request \): Response
    {
        \ = \->validate([
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);
        
        \ = \->service->createUser(\);
        
        return response()->json(\, 201);
    }
}
\\\

### Cycle ORM Models

\\\php
namespace App\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation;

#[Entity(repository: UserRepository::class)]
#[Table(name: 'users')]
class User
{
    #[Column(type: 'primary')]
    public int \;
    
    #[Column(type: 'string')]
    public string \;
    
    #[Column(type: 'string', unique: true)]
    public string \;
    
    #[Column(type: 'string')]
    public string \;
    
    #[Relation\HasMany(target: Post::class)]
    public array \ = [];
    
    #[Column(type: 'datetime')]
    public \DateTime \;
}
\\\

### Latte Templates

\\\latte
{* layouts/app.latte *}
<!DOCTYPE html>
<html>
<head>
    <title>{block title}NeoPhp Framework{/block}</title>
</head>
<body>
    <nav>
        <a n:href="/">Home</a>
        <a n:href="/about">About</a>
    </nav>
    
    {include content}
</body>
</html>

{* users/index.latte *}
{layout 'layouts/app.latte'}

{block title}Users - NeoPhp{/block}

{block content}
    <h1>Users</h1>
    
    <table>
        <tr n:foreach="\ as \">
            <td>{{\->name}}</td>
            <td>{{\->email}}</td>
            <td><a n:href="/users/{\->id}">View</a></td>
        </tr>
    </table>
    
    {{\->links()}}
{/block}
\\\

### API Development

\\\php
// REST API with authentication
\->prefix('/api')->middleware(['api'])->group(function(\) {
    // Public routes
    \->post('/register', [AuthController::class, 'register']);
    \->post('/login', [AuthController::class, 'login']);
    
    // Protected routes (JWT)
    \->middleware(['jwt.auth'])->group(function(\) {
        \->get('/user', [UserController::class, 'me']);
        \->resource('/posts', PostController::class);
    });
});

// API Controller
class PostController extends ApiController
{
    public function index(): JsonResponse
    {
        \ = Post::with('author')->paginate(20);
        return \->success(PostResource::collection(\));
    }
    
    public function store(StorePostRequest \): JsonResponse
    {
        \ = Post::create(\->validated());
        return \->created(new PostResource(\));
    }
}
\\\

---

## Requirements

- **PHP** 8.3 or higher
- **Composer** 2.0+
- **Database** MySQL 5.7+ / PostgreSQL 12+ / SQLite 3+
- **Extensions** PDO, JSON, Mbstring, OpenSSL

### Optional
- **Redis** 6.0+ (for caching/queues)
- **Memcached** 1.5+ (for caching)
- **Node.js** 18+ (for asset compilation)

---

## Installation

\\\ash
# Clone repository
git clone https://github.com/yourusername/neophpframework.git
cd neophpframework

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure database in .env
nano .env

# Generate application key
neo key:generate

# Run migrations
neo migrate

# Seed database (optional)
neo db:seed

# Start development server
neo serve
\\\

Visit **http://localhost:8000**

For detailed installation instructions, see [Installation Guide](docs/INSTALLATION.md).

---

## Project Structure

\\\
neophpframework/
├── app/                    # Application code
│   ├── Http/              # HTTP layer
│   │   ├── Controllers/   # Request handlers
│   │   ├── Middleware/    # HTTP middleware
│   │   └── Requests/      # Form requests & validation
│   ├── Entities/          # Cycle ORM entities
│   ├── Repositories/      # Data repositories
│   ├── Services/          # Business logic services
│   └── Providers/         # Service providers
│
├── config/                # Configuration files
│   ├── app.php           # Application config
│   ├── database.php      # Database connections
│   ├── cache.php         # Cache configuration
│   └── ...               # Other configs
│
├── neocore/              # Framework core
│   ├── src/              # Core source code
│   └── helpers/          # Helper functions
│
├── public/                # Web root (document root)
│   ├── index.php         # Application entry point
│   ├── assets/           # Compiled assets (CSS, JS)
│   └── uploads/          # Public uploads
│
├── resources/             # Application resources
│   ├── views/            # Latte templates
│   ├── lang/             # Language files
│   └── assets/           # Source assets (SCSS, JS)
│
├── routes/                # Route definitions
│   ├── web.php           # Web routes
│   ├── api.php           # API routes
│   └── console.php       # Console commands
│
├── storage/               # Storage directory
│   ├── cache/            # Application cache
│   ├── logs/             # Log files
│   ├── sessions/         # Session files
│   └── uploads/          # Private uploads
│
├── tests/                 # Test suite
│   ├── Feature/          # Feature tests
│   └── Unit/             # Unit tests
│
├── vendor/                # Composer dependencies
│
├── .env                   # Environment configuration
├── .env.example          # Environment template
├── composer.json         # PHP dependencies
├── neo                    # CLI entry point (Unix)
├── neo.bat               # CLI entry point (Windows)
└── README.md             # This file
\\\

---

## Why Choose NeoPhp Framework?

### Performance
- **2-3x faster than Laravel** thanks to Cycle ORM
- Compiled templates with aggressive caching
- Optimized query generation and execution
- Efficient memory usage

### Developer Experience
- Clean, intuitive APIs
- Comprehensive documentation
- Type-safe with PHP 8.3+ features
- Powerful CLI tools
- Hot reload in development

### Architecture
- Modular monolith design
- Dependency injection container
- Service-oriented architecture
- SOLID principles
- Repository pattern

### Security First
- JWT authentication out of the box
- RBAC with granular permissions
- CSRF protection enabled by default
- XSS prevention with auto-escaping
- SQL injection protection via ORM
- Secure password hashing (Bcrypt/Argon2)
- Rate limiting and throttling

### Feature Complete
Everything you need for modern web development:
- Database ORM with migrations
- Template engine
- Authentication & authorization
- Caching (Redis, Memcached)
- Queue system
- Real-time broadcasting
- File storage (Local, S3, CDN)
- Localization
- Testing utilities
- API development tools
- SEO & CMS features

### Production Ready
- Battle-tested components
- Error tracking integration
- Logging and monitoring
- Performance profiling
- Deployment tools
- Zero-downtime deployments

---

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Ways to Contribute
- Report bugs and issues
- Suggest new features
- Improve documentation
- Submit pull requests
- Write tests

### Development Setup

\\\ash
# Fork and clone
git clone https://github.com/yourusername/neophpframework.git
cd neophpframework

# Install dependencies
composer install

# Configure environment
cp .env.example .env

# Run tests
composer test

# Code style
composer format
\\\

---

## Security

If you discover a security vulnerability, please email **security@neophp.dev** instead of using the issue tracker.

See [SECURITY.md](SECURITY.md) for our security policy and supported versions.

---

## License

NeoPhp Framework is open-source software licensed under the [MIT license](LICENSE).

---

## Credits

Built with excellent open-source packages:

- [Cycle ORM](https://cycle-orm.dev/) - High-performance DataMapper ORM
- [Latte](https://latte.nette.org/) - Fast and secure template engine
- [PSR Logger](https://www.php-fig.org/psr/psr-3/) - Logging interface
- [Monolog](https://github.com/Seldaek/monolog) - Logging library

---

## Support

- **Documentation**: [docs/introduction.md](docs/introduction.md)
- **Issues**: [GitHub Issues](https://github.com/yourusername/neophpframework/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/neophpframework/discussions)
- **Email**: support@neophp.dev

---

<div align="center">

**Built with ❤ by developers who value clarity and performance**

[⭐ Star on GitHub](https://github.com/yourusername/neophpframework) • [📖 Read the Docs](docs/introduction.md) • [💬 Join Discussion](https://github.com/yourusername/neophpframework/discussions)

</div>
