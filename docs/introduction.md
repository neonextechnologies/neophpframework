# Introduction

## What is NeoPhp?

NeoPhp is a lightweight, explicit, and predictable PHP framework designed for developers who value clarity and control over magic. Built with principles inspired by CodeIgniter 4's explicitness, but with a modern, modular architecture.

## Why NeoPhp?

### No Magic

Everything in NeoPhp is explicit and traceable. There are no hidden mechanisms, no automatic behaviors, and no surprises. When you write code, you know exactly what it does.

```php
// Explicit routing - you define every route
$router->get('/users', 'App\\Http\\Controllers\\UserController@index');

// Explicit database queries - no hidden SQL
$users = $model->findWhere(['status' => 'active']);

// Explicit dependency passing - no auto-injection
$service = new UserService($database, $eventBus);
```

### Shared Hosting Ready

NeoPhp doesn't require Composer during runtime. Once installed, you can deploy to any shared hosting environment without worrying about dependencies.

### Lightweight

With minimal overhead and no unnecessary abstractions, NeoPhp is fast by default. The core framework is small, focused, and easy to understand.

### Modular

Build isolated modules that can be enabled or disabled. Each module is self-contained with its own controllers, models, routes, and migrations.

## Design Philosophy

### 1. Explicit Over Implicit

```php
// ❌ Bad: Magic behavior
User::create($data); // Where does this go? What happens?

// ✅ Good: Explicit behavior
$db = Database::connection();
$userModel = new User($db);
$userId = $userModel->insert($data);
```

### 2. No Service Container

Dependencies are passed explicitly through constructors or methods. No hidden service resolution.

```php
// ❌ No auto-injection
class UserController {
    public function __construct(UserService $service) {} // Magic!
}

// ✅ Explicit construction
class UserController extends Controller {
    private UserService $service;
    
    public function __construct() {
        $db = Database::connection();
        $this->service = new UserService($db);
    }
}
```

### 3. No Facades

All classes are instantiated normally. No static proxies hiding complexity.

```php
// ❌ No facades
DB::table('users')->get(); // What is DB? Where is the connection?

// ✅ Explicit usage
$db = Database::connection();
$model = new User($db);
$users = $model->findAll();
```

### 4. Simple Data Access

No ORM magic. Use PDO directly with helper methods for common operations.

```php
// Simple CRUD operations
$user = $userModel->find(1);
$userId = $userModel->insert(['name' => 'John']);
$userModel->update(1, ['name' => 'Jane']);
$userModel->delete(1);

// Custom queries when needed
$sql = "SELECT * FROM users WHERE created_at > :date";
$stmt = $pdo->prepare($sql);
$stmt->execute(['date' => $date]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

## When to Use NeoPhp

### Perfect For:

- ✅ **API-first applications** - Clean, predictable JSON responses
- ✅ **Microservices** - Lightweight, minimal dependencies
- ✅ **Learning PHP** - Clear code, no magic to understand
- ✅ **Shared hosting** - No Composer runtime requirement
- ✅ **Legacy modernization** - Easy to understand for CI/Laravel refugees
- ✅ **Full control needed** - You decide everything

### Not Ideal For:

- ❌ **Rapid prototyping** - More verbose than Laravel
- ❌ **Large teams used to Laravel** - Different conventions
- ❌ **Complex ORM needs** - No built-in relationships (but you can add Cycle ORM)
- ❌ **If you love magic** - This framework is explicitly anti-magic

## Core Components

### Router
Table-driven routing with explicit route definitions and middleware support.

### Request/Response
Thin wrappers around PHP's superglobals providing clean, testable interfaces.

### Controllers
Thin controllers that delegate to services. No magic methods.

### Models
Simple data access objects with PDO. No ORM, no relationships, just clean SQL.

### Services
Business logic layer. Keep controllers thin, put logic in services.

### Events
Synchronous event system. Register listeners and dispatch events explicitly.

### Queue
File or Redis-based background job processing.

### Modules
Isolated, self-contained feature modules that can be enabled/disabled.

### CLI
Command-line tool for scaffolding and running workers.

## Comparison with Other Frameworks

| Feature | NeoPhp | Laravel | CodeIgniter 4 |
|---------|---------|---------|---------------|
| Service Container | ❌ No | ✅ Yes | ✅ Yes |
| Facades | ❌ No | ✅ Yes | ❌ No |
| Auto-DI | ❌ No | ✅ Yes | ✅ Yes |
| ORM | ❌ No | ✅ Eloquent | ❌ No |
| Template Engine | ❌ No | ✅ Blade | ❌ No |
| Explicit Routing | ✅ Yes | ❌ No | ✅ Yes |
| Shared Hosting | ✅ Yes | ⚠️ Maybe | ✅ Yes |
| Module System | ✅ Yes | ⚠️ Packages | ✅ Yes |

## Next Steps

Ready to start? Head to the [Installation Guide](installation.md) to set up your first NeoPhp project.

Or explore:
- [Directory Structure](directory-structure.md) - Understand the layout
- [Routing](basics/routing.md) - Define your first routes
- [Controllers](basics/controllers.md) - Create your first controller
