# Welcome to NeoPhp Framework

NeoPhp Framework is a modern, full-stack PHP framework with modular architecture built on [Cycle ORM](https://cycle-orm.dev/) and [Latte Templates](https://latte.nette.org/). It provides everything you need to build production-ready web applications.

## Features

* 🚀 **High Performance** - Built with Cycle ORM (2-3x faster than Eloquent)
* 🧩 **Modular Architecture** - Self-contained modules with dependency injection
* 🔐 **Built-in Security** - JWT authentication & RBAC authorization
* 💾 **Database Integration** - Cycle ORM with PostgreSQL, MySQL, SQLite
* 🎨 **Frontend Support** - Latte template engine and asset management
* 🌐 **Advanced Features** - WebSockets, caching, queues, broadcasting

## Quick Start

```bash
# Clone repository
git clone https://github.com/yourusername/neophpframework.git
cd neophpframework

# Install dependencies
composer install

# Configure environment
cp .env.example .env
nano .env

# Generate key and migrate
neo key:generate
neo migrate

# Start server
neo serve
```

Your app is now running on `http://localhost:8000` with database, auth, and routing configured! 🎉

## What is NeoPhp Framework?

NeoPhp is a modern PHP framework designed for developers who value clarity, performance, and modular architecture. Built with principles of clean code and SOLID principles.

### Key Characteristics

**Modern PHP** - Built for PHP 8.3+ with typed properties, enums, and attributes

**Modular Monolith** - Organize code into self-contained modules that can scale

**Performance First** - Cycle ORM provides 2-3x better performance than traditional ORMs

**Developer Friendly** - Intuitive APIs with comprehensive documentation

**Production Ready** - Battle-tested components with security built-in

## Why NeoPhp Framework?

### Everything You Need

NeoPhp comes with everything needed for modern web development:

- **Authentication & Authorization** - JWT, sessions, RBAC, permissions
- **Database Layer** - Cycle ORM, migrations, seeding, relationships
- **API Development** - RESTful APIs, JSON resources, versioning
- **Caching** - Redis, Memcached, file-based caching
- **Queue System** - Background jobs, scheduling, workers
- **Real-time** - Broadcasting, WebSockets, events
- **Content Management** - SEO tools, CMS features, media handling
- **Testing Suite** - HTTP tests, database tests, mocking

### Clean Architecture

```php
// Explicit routing - you define every route
$router->get('/users', [UserController::class, 'index']);

// Type-safe controllers with dependency injection
class UserController
{
    public function __construct(
        private UserRepository $users,
        private UserService $service
    ) {}
    
    public function index(): Response
    {
        $users = $this->users->paginate(15);
        return view('users.index', compact('users'));
    }
}
```

### Modular Architecture

Build isolated modules that are self-contained:

```
modules/
├── Billing/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── routes.php
└── Reporting/
    ├── Controllers/
    ├── Models/
    └── routes.php
```

Each module can be developed, tested, and deployed independently while remaining part of a cohesive application.

## Architecture Overview

NeoPhp follows a **modular monolith architecture**:

- **Modular** - Code organized into bounded contexts
- **Monolith** - Single deployment unit for simplicity
- **Scalable** - Can be split into microservices when needed

### Core Components

**HTTP Layer** - Routing, middleware, request/response handling

**Database Layer** - Cycle ORM, repositories, migrations

**Service Layer** - Business logic, domain services

**Presentation Layer** - Latte templates, JSON resources

**Infrastructure** - Caching, queues, logging, monitoring

## Documentation

This guide covers everything from basics to advanced topics:

* **Getting Started** - Installation and first application
* **The Basics** - Routing, controllers, middleware, validation
* **Database & ORM** - Models, queries, migrations
* **Security** - Authentication, authorization, RBAC
* **Advanced Features** - Caching, queues, events, APIs
* **Testing** - HTTP tests, database tests, mocking
* **Deployment** - Production optimization

## Learn More

* [GitHub Repository](https://github.com/yourusername/neophpframework)
* [Community Forum](https://forum.neophp.dev)
* [Discord Channel](https://discord.gg/neophp)

---

Ready to get started? Head over to [Installation](INSTALLATION.md) to begin building with NeoPhp Framework!
