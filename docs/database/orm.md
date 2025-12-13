# Cycle ORM

NeoCore Framework มาพร้อมกับ **Cycle ORM** ซึ่งเป็น DataMapper ORM ที่รวดเร็วและปลอดภัย

## คุณสมบัติ

- 🚀 **2-3x เร็วกว่า Eloquent** - Schema compilation + aggressive caching
- 🔒 **Type-safe** - ใช้ PHP Attributes สำหรับ entity mapping
- 📊 **DataMapper Pattern** - แยก business logic ออกจาก database logic
- 🔄 **Lazy/Eager Loading** - ควบคุม query ได้อย่างละเอียด
- 🎯 **Repository Pattern** - Clean architecture

## การกำหนดค่า

ไฟล์ `config/orm.php`:

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

Entity คือ PHP Class ที่แทนตารางในฐานข้อมูล ใช้ PHP Attributes สำหรับ mapping:

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

### Annotations ที่ใช้บ่อย

| Annotation | Description | Example |
|------------|-------------|---------|
| `#[Entity]` | กำหนด Entity class | `#[Entity]` |
| `#[Table]` | ชื่อตาราง | `#[Table(name: 'users')]` |
| `#[Column]` | Field mapping | `#[Column(type: 'string')]` |
| `primary` | Primary key | `#[Column(type: 'primary')]` |
| `nullable` | ยอมรับ NULL | `#[Column(nullable: true)]` |
| `default` | ค่า default | `#[Column(default: 'active')]` |

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

Repository ใช้สำหรับ query ข้อมูล:

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

## การใช้งานใน Controller

```php
<?php

namespace App\Http\Controllers;

use NeoCore\System\Core\Controller;
use NeoCore\System\Core\ORMService;
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
        // สร้าง entity
        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->setPassword($request->input('password'));

        // บันทึก
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
// ดึง ORM instance
$orm = orm();

// ดึง Repository
$userRepo = repository(User::class);

// ดึง Entity Manager
$em = entity_manager();
```

## CLI Commands

### สร้าง Entity

```bash
php neo make:entity Product
```

สร้างไฟล์ `app/Entities/Product.php` พร้อม annotations

### สร้าง Repository

```bash
php neo make:repository ProductRepository
```

สร้างไฟล์ `app/Repositories/ProductRepository.php`

### Sync Schema

```bash
php neo orm:sync
```

สร้าง/อัปเดตตารางในฐานข้อมูลตาม Entity definitions

**Options:**
- `--run` - Execute schema changes (default: dry run)

### Clear Cache

```bash
php neo cache:clear
```

ล้าง ORM schema cache

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

1. **ใช้ Repository Pattern** - อย่า query ตรงจาก Entity
2. **Eager Loading** - ระวัง N+1 query problem
3. **Type Hints** - ใช้ type hints ทุก property
4. **Immutable Dates** - ใช้ `DateTimeImmutable` แทน `DateTime`
5. **Cache Schema** - Enable cache ใน production

## Performance Tips

- Schema compilation ทำครั้งเดียว แล้ว cache
- ใช้ `select()->load()` สำหรับ eager loading
- ใช้ `persist()` หลายตัวก่อน `run()` เพื่อ batch insert
- Enable query logging เฉพาะ development

## เปรียบเทียบกับ Eloquent

| Feature | Cycle ORM | Eloquent |
|---------|-----------|----------|
| Pattern | DataMapper | ActiveRecord |
| Speed | 2-3x faster | Baseline |
| Type Safety | Strict | Loose |
| Schema | Compiled | Runtime |
| Learning Curve | Medium | Easy |

