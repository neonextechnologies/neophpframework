# Quick Start

Get started with NeoPhp Framework in 5 minutes

## Installation

### Requirements

- PHP 8.0 or higher
- Composer
- Extensions: `pdo`, `json`, `mbstring`

### Clone Project

```bash
git clone https://github.com/yourusername/NeoPhp.git
cd NeoPhp
```

### Install Dependencies

```bash
composer install
```

## Configuration

### Database

Edit `config/database.php`:

```php
return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'NeoPhp_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

### Environment

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Edit `.env`:

```env
APP_ENV=development
APP_DEBUG=true

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=NeoPhp_db
DB_USERNAME=root
DB_PASSWORD=
```

## Sync Database Schema

Create tables from Entities:

```bash
php neo orm:sync --run
```

## Start Development Server

```bash
php -S localhost:8000 -t public/
```

Open browser: http://localhost:8000

## Create Entity

```bash
php neo make:entity Post
```

Edit `app/Entities/Post.php`:

```php
<?php

namespace App\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table(name: 'posts')]
class Post
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $title;

    #[Column(type: 'text')]
    public string $content;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
```

## Create Repository

```bash
php neo make:repository PostRepository
```

Edit `app/Repositories/PostRepository.php`:

```php
<?php

namespace App\Repositories;

use Cycle\ORM\Select\Repository as BaseRepository;

class PostRepository extends BaseRepository
{
    public function findRecent(int $limit = 10): array
    {
        return $this->select()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }
}
```

Update `config/orm.php`:

```php
'repositories' => [
    \App\Entities\User::class => \App\Repositories\UserRepository::class,
    \App\Entities\Product::class => \App\Repositories\ProductRepository::class,
    \App\Entities\Post::class => \App\Repositories\PostRepository::class,
],
```

## Create Controller

Create file `app/Http/Controllers/PostController.php`:

```php
<?php

namespace App\Http\Controllers;

use NeoPhp\System\Core\Controller;
use NeoPhp\System\Core\Request;
use NeoPhp\System\Core\Response;
use NeoPhp\System\Core\ORMService;
use App\Entities\Post;
use App\Repositories\PostRepository;

class PostController extends Controller
{
    private PostRepository $postRepository;

    public function __construct()
    {
        $this->postRepository = ORMService::getRepository(Post::class);
    }

    public function index(Request $request, Response $response): Response
    {
        $posts = $this->postRepository->findRecent(20);
        
        return $this->view($response, 'posts/index', [
            'posts' => $posts
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        // Validate
        $errors = $this->validate($request->all(), [
            'title' => 'required|min:5',
            'content' => 'required|min:10',
        ]);

        if (!empty($errors)) {
            return $this->respondValidationError($response, $errors);
        }

        // Create post
        $post = new Post();
        $post->title = $request->input('title');
        $post->content = $request->input('content');

        // Save
        $entityManager = ORMService::getEntityManager();
        $entityManager->persist($post);
        $entityManager->run();

        return $this->respondSuccess($response, [
            'id' => $post->id
        ], 'Post created successfully');
    }
}
```

## Create View

Create file `resources/views/posts/index.latte`:

```latte
{extends 'layouts/app.latte'}

{block title}Posts{/block}

{block content}
    <h2>Recent Posts</h2>

    {if count($posts) > 0}
        {foreach $posts as $post}
            <article class="post">
                <h3>{$post->title}</h3>
                <p>{$post->content|truncate: 200}</p>
                <small>Posted on {$post->createdAt|date: 'F j, Y'}</small>
            </article>
        {/foreach}
    {else}
        <p>No posts yet.</p>
    {/if}
{/block}
```

## Add Routes

Edit `config/routes.php`:

```php
return function($router) {
    $router->get('/', 'App\\Http\\Controllers\\HomeController@index');
    
    // Posts
    $router->get('/posts', 'App\\Http\\Controllers\\PostController@index');
    $router->post('/posts', 'App\\Http\\Controllers\\PostController@store');
};
```

## Sync Schema

```bash
php neo orm:sync --run
```

## Test

Open browser: http://localhost:8000/posts

## CLI Commands

```bash
# Create Entity
php neo make:entity EntityName

# Create Repository
php neo make:repository RepositoryName

# Create Controller
php neo make:controller ControllerName

# Create Middleware
php neo make:middleware MiddlewareName

# Sync ORM Schema
php neo orm:sync --run

# Clear caches
php neo cache:clear
php neo view:clear

# Run migrations
php neo migrate

# Create migration
php neo make:migration create_posts_table

# List routes
php neo route:list

# Queue worker
php neo queue:work

# Help
php neo --help
```

## Next Steps

- [Routing](routing.md) - Learn routing
- [Controllers](controllers.md) - Learn controllers
- [ORM](../database/orm.md) - Deep dive into Cycle ORM
- [Views](views.md) - Learn Latte Templates
- [Validation](validation.md) - Data validation
- [Middleware](middleware.md) - HTTP middleware
- [Events](../advanced/events.md) - Event system
- [Queue](../advanced/queue.md) - Background jobs

## Example Project Structure

```
NeoPhp/
├── app/
│   ├── Entities/
│   │   ├── User.php
│   │   ├── Post.php
│   │   └── Product.php
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── PostRepository.php
│   │   └── ProductRepository.php
│   └── Http/
│       ├── Controllers/
│       │   ├── HomeController.php
│       │   ├── PostController.php
│       │   └── UserController.php
│       └── Middleware/
│           └── AuthMiddleware.php
├── config/
│   ├── database.php
│   ├── orm.php
│   ├── view.php
│   └── routes.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.latte
│       │   └── messages.latte
│       ├── home.latte
│       ├── posts/
│       │   └── index.latte
│       └── users/
│           └── index.latte
├── storage/
│   ├── cache/
│   │   ├── cycle/
│   │   └── views/
│   └── logs/
├── system/
│   └── Core/
├── public/
│   └── index.php
└── neo (CLI)
```

## Best Practices

1. **Entities** - Use Type Hints for all properties
2. **Repositories** - Keep query logic in Repository
3. **Controllers** - Keep it thin, move business logic to Service
4. **Views** - Use Layouts and Partials
5. **Validation** - Validate all user input
6. **Security** - Use prepared statements (ORM handles this)
7. **Cache** - Enable cache in production

## Tips

- Use `php neo --help` to see all commands
- Enable debug mode during development: `APP_DEBUG=true`
- Sync schema after modifying Entity: `php neo orm:sync --run`
- Clear cache after modifying config: `php neo cache:clear`
- Use Repository instead of querying Entity directly

## Troubleshooting

### Port 8000 already in use

```bash
php -S localhost:8080 -t public/
```

### Database Connection Error

Check `config/database.php` and MySQL permissions

### Class Not Found

```bash
composer dump-autoload
```

### Template Not Found

Check path in `config/view.php`

### Permission Denied

```bash
chmod -R 755 storage/
```
