# Database Models

Work with database tables using object-oriented models with Cycle ORM.

## Defining Models

### Basic Model

```php
namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'users')]
class User
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $name;
    
    #[Column(type: 'string')]
    public string $email;
    
    #[Column(type: 'string')]
    public string $password;
    
    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;
    
    #[Column(type: 'datetime')]
    public \DateTimeInterface $updated_at;
}
```

### Model with Options

```php
#[Entity(
    table: 'users',
    database: 'default',
    repository: UserRepository::class
)]
class User
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string', nullable: false)]
    public string $name;
    
    #[Column(type: 'string', unique: true)]
    public string $email;
    
    #[Column(type: 'string', default: 'active')]
    public string $status;
}
```

## Column Types

### Available Types

```php
#[Entity]
class Product
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string', length: 255)]
    public string $name;
    
    #[Column(type: 'text')]
    public string $description;
    
    #[Column(type: 'integer')]
    public int $stock;
    
    #[Column(type: 'float')]
    public float $price;
    
    #[Column(type: 'decimal', precision: 10, scale: 2)]
    public float $discount;
    
    #[Column(type: 'boolean')]
    public bool $active;
    
    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;
    
    #[Column(type: 'date')]
    public \DateTimeInterface $published_date;
    
    #[Column(type: 'time')]
    public \DateTimeInterface $available_time;
    
    #[Column(type: 'json')]
    public array $meta;
    
    #[Column(type: 'enum', values: ['draft', 'published', 'archived'])]
    public string $status;
}
```

### Nullable Columns

```php
#[Entity]
class Post
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $title;
    
    #[Column(type: 'text', nullable: true)]
    public ?string $excerpt = null;
    
    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $published_at = null;
}
```

## Creating Records

### Basic Create

```php
use Cycle\ORM\EntityManager;

$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->password = password_hash('secret', PASSWORD_BCRYPT);
$user->created_at = new \DateTime();
$user->updated_at = new \DateTime();

$entityManager = new EntityManager($orm);
$entityManager->persist($user);
$entityManager->run();
```

### Mass Assignment

```php
class User
{
    public static function create(array $attributes): self
    {
        $user = new self();
        $user->fill($attributes);
        return $user;
    }
    
    public function fill(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

// Usage
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

## Reading Records

### Find by ID

```php
use Cycle\ORM\ORM;

$orm = app(ORM::class);
$repository = $orm->getRepository(User::class);

// Find by primary key
$user = $repository->findByPK(1);

// Find one by criteria
$user = $repository->findOne(['email' => 'john@example.com']);

// Find all
$users = $repository->findAll();

// Find by criteria
$users = $repository->findAll(['active' => true]);
```

### Query Methods

```php
$repository = $orm->getRepository(User::class);

// Select with conditions
$users = $repository->select()
    ->where('active', true)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->fetchAll();

// Count
$count = $repository->select()
    ->where('role', 'admin')
    ->count();

// First or fail
$user = $repository->select()
    ->where('email', 'john@example.com')
    ->fetchOne();
```

## Updating Records

### Update Single Record

```php
$user = $repository->findByPK(1);
$user->name = 'Jane Doe';
$user->updated_at = new \DateTime();

$entityManager->persist($user);
$entityManager->run();
```

### Update Multiple

```php
$users = $repository->findAll(['role' => 'user']);

foreach ($users as $user) {
    $user->verified = true;
    $entityManager->persist($user);
}

$entityManager->run();
```

## Deleting Records

### Soft Delete

```php
#[Entity]
class Post
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $title;
    
    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $deleted_at = null;
    
    public function delete(): void
    {
        $this->deleted_at = new \DateTime();
    }
    
    public function restore(): void
    {
        $this->deleted_at = null;
    }
    
    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }
}
```

### Hard Delete

```php
$user = $repository->findByPK(1);

$entityManager->delete($user);
$entityManager->run();
```

## Scopes

### Global Scopes

```php
class Post
{
    public static function published()
    {
        return app(ORM::class)
            ->getRepository(self::class)
            ->select()
            ->where('published_at', '<=', new \DateTime())
            ->where('status', 'published');
    }
    
    public static function draft()
    {
        return app(ORM::class)
            ->getRepository(self::class)
            ->select()
            ->where('status', 'draft');
    }
}

// Usage
$posts = Post::published()->fetchAll();
$drafts = Post::draft()->fetchAll();
```

## Accessors & Mutators

### Getters & Setters

```php
#[Entity]
class User
{
    #[Column(type: 'string')]
    private string $email;
    
    public function getEmail(): string
    {
        return strtolower($this->email);
    }
    
    public function setEmail(string $email): void
    {
        $this->email = strtolower($email);
    }
    
    #[Column(type: 'string')]
    private string $password;
    
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }
}
```

### Computed Properties

```php
#[Entity]
class User
{
    #[Column(type: 'string')]
    public string $first_name;
    
    #[Column(type: 'string')]
    public string $last_name;
    
    public function getFullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

// Usage
echo $user->getFullName(); // "John Doe"
```

## Type Casting

### Cast Attributes

```php
#[Entity]
class Post
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'json')]
    private string $meta_json;
    
    public function getMeta(): array
    {
        return json_decode($this->meta_json, true);
    }
    
    public function setMeta(array $meta): void
    {
        $this->meta_json = json_encode($meta);
    }
    
    #[Column(type: 'boolean')]
    public bool $published;
    
    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;
}
```

## Events

### Model Events

```php
#[Entity]
class User
{
    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }
    
    public function beforeSave(): void
    {
        $this->updated_at = new \DateTime();
    }
    
    public function afterSave(): void
    {
        // Clear cache
        cache()->forget("user:{$this->id}");
    }
}
```

## Repositories

### Custom Repository

```php
namespace App\Repositories;

use Cycle\ORM\Select\Repository;

class UserRepository extends Repository
{
    public function findActive(): array
    {
        return $this->select()
            ->where('active', true)
            ->fetchAll();
    }
    
    public function findByEmail(string $email): ?User
    {
        return $this->findOne(['email' => $email]);
    }
    
    public function admins(): array
    {
        return $this->select()
            ->where('role', 'admin')
            ->orderBy('name')
            ->fetchAll();
    }
}
```

### Use Custom Repository

```php
#[Entity(repository: UserRepository::class)]
class User
{
    // ...
}

// Usage
$repository = $orm->getRepository(User::class);
$admins = $repository->admins();
$user = $repository->findByEmail('john@example.com');
```

## Timestamps

### Automatic Timestamps

```php
#[Entity]
class Post
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $title;
    
    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;
    
    #[Column(type: 'datetime')]
    public \DateTimeInterface $updated_at;
    
    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }
    
    public function touch(): void
    {
        $this->updated_at = new \DateTime();
    }
}
```

## Best Practices

1. **Use Type Hints** - Always type hint properties
2. **Validation** - Validate data before saving
3. **Accessors/Mutators** - Use for data transformation
4. **Repositories** - Create custom repositories for complex queries
5. **Timestamps** - Include created_at and updated_at
6. **Soft Deletes** - Use soft deletes for important data
7. **Events** - Use events for side effects
8. **Caching** - Cache frequently accessed models
9. **Eager Loading** - Load relationships to avoid N+1
10. **Tests** - Write tests for model behavior

## See Also

- [Relationships](relationships.md)
- [Migrations](migrations.md)
- [Query Builder](query-builder.md)
- [Seeding](seeding.md)
