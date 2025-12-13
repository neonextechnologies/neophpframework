<div align="center">

# NeoCore PHP Framework

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**A lightweight, explicit, and predictable PHP framework**

*Built for developers who value clarity and control over magic*

[Quick Start](#-quick-start) • [Documentation](#-documentation) • [Features](#-features) • [Contributing](CONTRIBUTING.md)

</div>

---

## 🎯 Design Philosophy

- **No Magic**: Everything is explicit and traceable
- **No Facades**: No hidden static proxies
- **No Auto-DI**: No service container or automatic dependency injection
- **No Auto-Discovery**: Routes and modules must be registered explicitly
- **Built-in ORM**: Cycle ORM with DataMapper pattern (2-3x faster than Eloquent)
- **Built-in Templates**: Latte Template Engine (90% similar to Blade, 2x faster)
- **Shared Hosting Ready**: Runs without Composer during runtime

## 📁 Structure

```
/neocore
├── public/              # Web root
│   └── index.php        # Entry point
├── app/                 # Application code
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Services/
│   └── Policies/
├── modules/             # Isolated modules
├── system/              # Framework core
│   ├── Core/
│   ├── CLI/
│   └── Helpers/
├── config/              # Configuration files
└── storage/             # Logs, cache, sessions
```

## 📦 Installation

```bash
# Clone repository
git clone https://github.com/yourusername/neocore.git
cd neocore

# Install dependencies (optional - for development)
composer install

# Copy environment file
cp .env.example .env

# Configure database in .env
# Then run migrations
php neocore migrate

# Start development server
php neocore serve
```

Visit `http://localhost:8000` 🚀

## 📝 CLI Commands

| Command | Description |
|---------|-------------|
| `make:module <name>` | Create new module |
| `make:controller <name>` | Create new controller |
| `make:entity <name>` | Create new Cycle ORM entity |
| `make:repository <name>` | Create new repository |
| `make:model <name>` | Create new model |
| `make:service <name>` | Create new service |
| `make:migration <name>` | Create new migration |
| `orm:sync [--run]` | Sync ORM schema to database |
| `view:clear` | Clear Latte template cache |
| `cache:clear` | Clear ORM schema cache |
| `migrate` | Run migrations |
| `migrate:rollback` | Rollback migrations |
| `worker:run [queue]` | Start queue worker |
| `serve [host] [port]` | Start dev server |
| `list` | List all commands |

## 🚀 Quick Start

### Step 1: Create a Controller

```bash
php neocore make:controller UserController
```

### Step 2: Define Routes

Edit `config/routes.php`:

```php
$router->get('/users', 'App\\Http\\Controllers\\UserController@index');
$router->post('/users', 'App\\Http\\Controllers\\UserController@store');
$router->get('/users/{id}', 'App\\Http\\Controllers\\UserController@show');
```

### Step 3: Write Controller Logic

```php
namespace App\Http\Controllers;

use NeoCore\System\Core\Controller;
use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

class UserController extends Controller
{
    public function index(Request $request, Response $response): Response
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Smith']
        ];
        return $this->respondSuccess($response, $users);
    }
}
```

Visit `http://localhost:8000/users` and see JSON response!

## 📚 Documentation

- **[Contributing Guidelines](CONTRIBUTING.md)** - How to contribute
- **[Security Policy](SECURITY.md)** - Report vulnerabilities
- **[Changelog](CHANGELOG.md)** - Version history

## 🔧 Core Features

### Routing

```php
// Basic routes
$router->get('/users', 'App\\Http\\Controllers\\UserController@index');
$router->post('/users', 'App\\Http\\Controllers\\UserController@store');
$router->get('/users/{id}', 'App\\Http\\Controllers\\UserController@show');

// Route groups with prefix
$router->prefix('/api/v1')->group(function($router) {
    $router->get('/posts', 'PostController@index');
});

// Middleware
$router->middleware(['auth'])->group(function($router) {
    $router->get('/profile', 'ProfileController@show');
});
```

### Controllers

```php
namespace App\Http\Controllers;

use NeoCore\System\Core\Controller;
use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

class UserController extends Controller
{
    public function store(Request $request, Response $response): Response
    {
        $data = $request->all();
        
        // Validation
        $errors = $this->validate($data, [
            'name' => 'required|min:3',
            'email' => 'required|email',
        ]);

        if (!empty($errors)) {
            return $this->respondValidationError($response, $errors);
        }

        return $this->respondSuccess($response, $data, 'User created', 201);
    }
}
```

### Database with Cycle ORM

```php
namespace App\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(repository: \App\Repositories\UserRepository::class)]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string')]
    public string $email;
}
```

**Repository:**

```php
namespace App\Repositories;

use Cycle\ORM\Select\Repository;

class UserRepository extends Repository
{
    public function findByEmail(string $email): ?User
    {
        return $this->findOne(['email' => $email]);
    }
    
    public function findActive(): array
    {
        return $this->select()
            ->where('status', 'active')
            ->fetchAll();
    }
}
```

**Usage in Controller:**

```php
use NeoCore\System\Core\ORMService;

$userRepo = ORMService::getRepository(User::class);
$user = $userRepo->findByEmail('user@example.com');
```

### Views with Latte Templates

**Layout** (`resources/views/layouts/app.latte`):

```latte
<!DOCTYPE html>
<html>
<head>
    <title>{block title}NeoCore{/block}</title>
</head>
<body>
    {block content}Default content{/block}
</body>
</html>
```

**Page** (`resources/views/users/index.latte`):

```latte
{extends 'layouts/app.latte'}

{block title}Users{/block}

{block content}
    <h2>Users</h2>
    {foreach $users as $user}
        <div>
            <h3>{$user->name}</h3>
            <p>{$user->email}</p>
        </div>
    {/foreach}
{/block}
```

**Controller:**

```php
public function index(Request $request, Response $response): Response
{
    $users = $this->userRepository->findActive();
    
    return $this->view($response, 'users/index', [
        'users' => $users
    ]);
}
```

### Queue System

```php
use NeoCore\System\Core\Queue;

$queue = new Queue('file', ['path' => './storage/queue']);

// Push job
$queue->push('emails', [
    'handler' => 'App\\Jobs\\SendEmailJob',
    'data' => ['email' => 'user@example.com']
]);
```

Run worker: `php neocore worker:run emails`

### Middleware

```php
namespace App\Http\Middleware;

use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        if (!isset($_SESSION['user_id'])) {
            return $response->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request, $response);
    }
}
```

## ⚙️ Configuration

**Database** (`config/database.php`):

```php
return [
    'default' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'neocore',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
```

## 📦 Using Composer Packages

NeoCore comes with **Cycle ORM** and **Latte Templates** built-in. You can add more packages:

```bash
# Validation
composer require respect/validation

# Logging
composer require monolog/monolog

# HTTP Client
composer require guzzlehttp/guzzle

# Image Processing
composer require intervention/image
```

See full examples in [COMPOSER_PACKAGES.md](COMPOSER_PACKAGES.md)

## 📋 Requirements

- **PHP 8.0+**
- **PDO extension**
- **JSON extension**
- mod_rewrite (Apache) or equivalent

## 🎯 Core Principles

| ✅ What NeoCore HAS | ❌ What NeoCore DOES NOT Have |
|---------------------|------------------------------|
| Explicit Routing | Service Container |
| Simple Controllers | Facades |
| **Cycle ORM (DataMapper)** | Auto Dependency Injection |
| **Latte Templates** | Route Model Binding |
| Queue System | Auto-Discovery |
| Event System | Magic Methods |
| Module System | Composer Runtime Dependency |
| CLI Tools | |
| Multi-Tenancy | |
| Migration System | |

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 🔒 Security

For security issues, please see [SECURITY.md](SECURITY.md).

## 📄 License

MIT License - see [LICENSE](LICENSE) file.

---

<div align="center">

**NeoCore** - Simple. Explicit. Predictable.

Made with ❤️ by developers who value clarity

</div>
