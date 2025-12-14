# Database Relationships

Define and work with model relationships using Cycle ORM.

## One-to-One

### Define Relationship

```php
use Cycle\Annotated\Annotation\Relation\HasOne;

#[Entity]
class User
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[HasOne(target: Profile::class)]
    public ?Profile $profile = null;
}

#[Entity]
class Profile
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'integer')]
    public int $user_id;
    
    #[BelongsTo(target: User::class)]
    public User $user;
}
```

### Access Relationship

```php
$user = $repository->findByPK(1);
echo $user->profile->bio;

$profile = $profileRepository->findByPK(1);
echo $profile->user->name;
```

## One-to-Many

### Define Relationship

```php
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class User
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[HasMany(target: Post::class)]
    public array $posts = [];
}

#[Entity]
class Post
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'integer')]
    public int $user_id;
    
    #[BelongsTo(target: User::class)]
    public User $user;
}
```

### Access Relationship

```php
$user = $repository->findByPK(1);

foreach ($user->posts as $post) {
    echo $post->title;
}

$post = $postRepository->findByPK(1);
echo $post->user->name;
```

## Many-to-Many

### Define Relationship

```php
use Cycle\Annotated\Annotation\Relation\ManyToMany;

#[Entity]
class User
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[ManyToMany(target: Role::class, through: 'user_roles')]
    public array $roles = [];
}

#[Entity]
class Role
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[ManyToMany(target: User::class, through: 'user_roles')]
    public array $users = [];
}
```

### Access Relationship

```php
$user = $repository->findByPK(1);

foreach ($user->roles as $role) {
    echo $role->name;
}

// Attach role
$user->roles[] = $adminRole;
$em->persist($user);
$em->run();
```

## Eager Loading

### Load Relationships

```php
use Cycle\ORM\Select;

// Load with relationship
$users = $repository->select()
    ->load('posts')
    ->fetchAll();

// Load multiple relationships
$users = $repository->select()
    ->load('posts')
    ->load('profile')
    ->fetchAll();

// Nested loading
$users = $repository->select()
    ->load('posts.comments')
    ->fetchAll();
```

## Lazy Loading

### Load on Demand

```php
$user = $repository->findByPK(1);

// Posts are loaded when accessed
foreach ($user->posts as $post) {
    echo $post->title;
}
```

## Relationship Queries

### Query Relationships

```php
// Users with posts
$users = $repository->select()
    ->where('posts.status', 'published')
    ->load('posts')
    ->fetchAll();

// Count relationships
$users = $repository->select()
    ->load('posts', [
        'method' => Select\Loader::INLOAD,
        'where' => ['status' => 'published']
    ])
    ->fetchAll();
```

## Has Many Through

### Define Through Relationship

```php
#[Entity]
class Country
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[HasMany(target: User::class)]
    public array $users = [];
}

#[Entity]
class User
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'integer')]
    public int $country_id;
    
    #[HasMany(target: Post::class)]
    public array $posts = [];
}

#[Entity]
class Post
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'integer')]
    public int $user_id;
}

// Access: $country->users->posts
```

## Polymorphic Relations

### One-to-Many Polymorphic

```php
#[Entity]
class Comment
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'integer')]
    public int $commentable_id;
    
    #[Column(type: 'string')]
    public string $commentable_type;
    
    public function commentable()
    {
        $class = $this->commentable_type;
        $orm = app(ORM::class);
        return $orm->getRepository($class)->findByPK($this->commentable_id);
    }
}

#[Entity]
class Post
{
    #[Column(type: 'primary')]
    public int $id;
    
    public function comments(): array
    {
        $orm = app(ORM::class);
        return $orm->getRepository(Comment::class)
            ->select()
            ->where('commentable_type', Post::class)
            ->where('commentable_id', $this->id)
            ->fetchAll();
    }
}
```

## Best Practices

1. **Eager Load** - Use eager loading to avoid N+1 queries
2. **Lazy Load** - Use lazy loading for optional relationships
3. **Index Foreign Keys** - Always index foreign key columns
4. **Cascade Deletes** - Use cascade for dependent records
5. **Bidirectional** - Define inverse relationships when needed
6. **Load Selectively** - Only load relationships you need
7. **Count Queries** - Use count instead of loading all records

## See Also

- [Models](models.md)
- [Query Builder](query-builder.md)
- [Getting Started](getting-started.md)
