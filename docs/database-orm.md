# Cycle ORM

NeoPhp Framework comes with **Cycle ORM**, which is a fast and secure DataMapper ORM

## Features

- **2-3x faster than Eloquent** - Schema compilation + aggressive caching
- **Type-safe** - Uses PHP Attributes for entity mapping
- **DataMapper Pattern** - Separates business logic from database logic
- **Lazy/Eager Loading** - Fine-grained query control
- **Repository Pattern** - Clean architecture

## Configuration

File `config/orm.php`:

```php
return [
    'entity_paths' => [
        __DIR__ . '/../app/Entities',
    ],
    
    'repositories' => [
        \App\Entities\User::class => \App\Repositories\UserRepository::class,
    ],
    
    'cache_dir' => __DIR__ . '/../storage/cache/cycle',
];
```

## Entity

Entity is a PHP Class that represents a database table. Uses PHP Attributes for mapping:

```php
<?php

namespace App\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;

#[Entity(repository: \App\Repositories\UserRepository::class)]
#[Table(name: 'users')]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string')]
    public string $email;

    #[Column(type: 'string')]
    private string $password;

    #[Column(type: 'string')]
    public string $status = 'active';

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $lastLogin = null;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}
```

### Commonly Used Annotations

| Annotation | Description | Example |
|------------|-------------|---------|
| `#[Entity]` | Define Entity class | `#[Entity]` |
| `#[Table]` | Table name | `#[Table(name: 'users')]` |
| `#[Column]` | Field mapping | `#[Column(type: 'string')]` |
| `primary` | Primary key | `#[Column(type: 'primary')]` |
| `nullable` | Accepts NULL | `#[Column(nullable: true)]` |
| `default` | Default value | `#[Column(default: 'active')]` |

### Column Types

- `primary` - Auto-increment ID
- `string` - VARCHAR
- `text` - TEXT
- `int` / `integer` - INT
- `bigint` - BIGINT
- `float` / `decimal` - DECIMAL
- `bool` / `boolean` - BOOLEAN
- `datetime` - DATETIME
- `date` - DATE
- `time` - TIME
- `json` - JSON

## Repository

Repository is used for querying data:

```php
<?php

namespace App\Repositories;

use Cycle\ORM\Select\Repository as BaseRepository;
use App\Entities\User;

class UserRepository extends BaseRepository
{
    public function findByEmail(string $email): ?User
    {
        return $this->findOne(['email' => $email]);
    }

    public function findActive(int $limit = 50): array
    {
        return $this->select()
            ->where('status', 'active')
            ->limit($limit)
            ->fetchAll();
    }

    public function search(string $keyword): array
    {
        return $this->select()
            ->where('name', 'like', "%{$keyword}%")
            ->orWhere('email', 'like', "%{$keyword}%")
            ->fetchAll();
    }
}
```

### Query Methods

#### Basic Queries

```php
// Find by primary key
$user = $repository->findByPK(1);

// Find one
$user = $repository->findOne(['email' => 'user@example.com']);

// Find all
$users = $repository->findAll();
```

#### Select Builder

```php
$users = $repository->select()
    ->where('status', 'active')
    ->where('created_at', '>', '2024-01-01')
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->offset(20)
    ->fetchAll();
```

#### Operators

```php
// WHERE status = 'active'
->where('status', '=', 'active')

// WHERE age > 18
->where('age', '>', 18)

// WHERE name LIKE '%john%'
->where('name', 'like', '%john%')

// OR WHERE
->orWhere('role', 'admin')

// IN
->where('id', 'in', [1, 2, 3])

// BETWEEN
->where('age', 'between', [18, 30])
```

## Using in Controller

```php
<?php

namespace App\Http\Controllers;

use NeoPhp\System\Core\Controller;
use NeoPhp\System\Core\ORMService;
use App\Entities\User;
use App\Repositories\UserRepository;

class UserController extends Controller
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = ORMService::getRepository(User::class);
    }

    public function index()
    {
        $users = $this->userRepository->findActive(100);
        
        return $this->respondSuccess($response, $users);
    }

    public function store(Request $request, Response $response)
    {
        // Create entity
        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->setPassword($request->input('password'));

        // Save
        $entityManager = ORMService::getEntityManager();
        $entityManager->persist($user);
        $entityManager->run();

        return $this->respondSuccess($response, [
            'id' => $user->id
        ]);
    }

    public function update(Request $request, Response $response)
    {
        $user = $this->userRepository->findByPK($request->param('id'));
        
        if (!$user) {
            return $this->respondNotFound($response);
        }

        // Update
        $user->name = $request->input('name');
        
        $entityManager = ORMService::getEntityManager();
        $entityManager->persist($user);
        $entityManager->run();

        return $this->respondSuccess($response);
    }

    public function delete(Request $request, Response $response)
    {
        $user = $this->userRepository->findByPK($request->param('id'));
        
        $entityManager = ORMService::getEntityManager();
        $entityManager->delete($user);
        $entityManager->run();

        return $this->respondSuccess($response);
    }
}
```

## Helper Functions

```php
// Get ORM instance
$orm = orm();

// Get Repository
$userRepo = repository(User::class);

// Get Entity Manager
$em = entity_manager();
```

## CLI Commands

### Create Entity

```bash
php neo make:entity Product
```

Creates file `app/Entities/Product.php` with annotations

### Create Repository

```bash
php neo make:repository ProductRepository
```

Creates file `app/Repositories/ProductRepository.php`

### Sync Schema

```bash
php neo orm:sync
```

Create/update database tables based on Entity definitions

**Options:**
- `--run` - Execute schema changes (default: dry run)

### Clear Cache

```bash
php neo cache:clear
```

Clear ORM schema cache

## Relationships

### One-to-Many

```php
#[Entity]
class Post
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'int')]
    public int $userId;

    #[BelongsTo(target: User::class)]
    public User $user;
}

#[Entity]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[HasMany(target: Post::class)]
    public array $posts = [];
}
```

### Many-to-Many

```php
#[Entity]
class User
{
    #[ManyToMany(target: Role::class, through: UserRole::class)]
    public array $roles = [];
}

#[Entity]
class Role
{
    #[ManyToMany(target: User::class, through: UserRole::class)]
    public array $users = [];
}
```

## Eager Loading

```php
// N+1 Problem
$users = $repository->findAll();
foreach ($users as $user) {
    echo $user->posts; // Query every loop!
}

// Solution: Eager Load
$users = $repository->select()
    ->load('posts')
    ->fetchAll();
```

## Transactions

```php
$entityManager = entity_manager();

try {
    $entityManager->persist($user);
    $entityManager->persist($profile);
    $entityManager->run();
} catch (\Exception $e) {
    // Rollback automatic
    throw $e;
}
```

## Best Practices

1. **Use Repository Pattern** - Don't query directly from Entity
2. **Eager Loading** - Watch out for N+1 query problem
3. **Type Hints** - Use type hints on all properties
4. **Immutable Dates** - Use `DateTimeImmutable` instead of `DateTime`
5. **Cache Schema** - Enable cache in production

## Performance Tips

- Schema compilation done once, then cached
- Use `select()->load()` for eager loading
- Use multiple `persist()` calls before `run()` for batch insert
- Enable query logging only in development

## Comparison with Eloquent

| Feature | Cycle ORM | Eloquent |
|---------|-----------|----------|
| Pattern | DataMapper | ActiveRecord |
| Speed | 2-3x faster | Baseline |
| Type Safety | Strict | Loose |
| Schema | Compiled | Runtime |
| Learning Curve | Medium | Easy |

