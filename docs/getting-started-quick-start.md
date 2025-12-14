# Quick Start

เริ่มต้นใช้งาน NeoPhp Framework ได้ใน 5 นาที

## ติดตั้ง

### Requirements

- PHP 8.0 หรือสูงกว่า
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

แก้ไขไฟล์ `config/database.php`:

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

คัดลอก `.env.example` เป็น `.env`:

```bash
cp .env.example .env
```

แก้ไข `.env`:

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

สร้างตารางจาก Entities:

```bash
php neo orm:sync --run
```

## Start Development Server

```bash
php -S localhost:8000 -t public/
```

เปิดเบราว์เซอร์: http://localhost:8000

## สร้าง Entity

```bash
php neo make:entity Post
```

แก้ไข `app/Entities/Post.php`:

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

## สร้าง Repository

```bash
php neo make:repository PostRepository
```

แก้ไข `app/Repositories/PostRepository.php`:

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

อัปเดต `config/orm.php`:

```php
'repositories' => [
    \App\Entities\User::class => \App\Repositories\UserRepository::class,
    \App\Entities\Product::class => \App\Repositories\ProductRepository::class,
    \App\Entities\Post::class => \App\Repositories\PostRepository::class,
],
```

## สร้าง Controller

สร้างไฟล์ `app/Http/Controllers/PostController.php`:

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

## สร้าง View

สร้างไฟล์ `resources/views/posts/index.latte`:

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

## เพิ่ม Routes

แก้ไข `config/routes.php`:

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

## ทดสอบ

เปิดเบราว์เซอร์: http://localhost:8000/posts

## CLI Commands

```bash
# สร้าง Entity
php neo make:entity EntityName

# สร้าง Repository
php neo make:repository RepositoryName

# สร้าง Controller
php neo make:controller ControllerName

# สร้าง Middleware
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

## ขั้นตอนถัดไป

- [Routing](routing.md) - เรียนรู้การสร้าง routes
- [Controllers](controllers.md) - เรียนรู้การสร้าง controllers
- [ORM](../database/orm.md) - เจาะลึก Cycle ORM
- [Views](views.md) - เรียนรู้ Latte Templates
- [Validation](validation.md) - ตรวจสอบข้อมูล
- [Middleware](middleware.md) - HTTP middleware
- [Events](../advanced/events.md) - Event system
- [Queue](../advanced/queue.md) - Background jobs

## ตัวอย่าง Project Structure

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

1. **Entities** - ใช้ Type Hints ทุก property
2. **Repositories** - เก็บ query logic ใน Repository
3. **Controllers** - Keep it thin, ย้าย business logic ไป Service
4. **Views** - ใช้ Layouts และ Partials
5. **Validation** - Validate ทุก user input
6. **Security** - ใช้ prepared statements (ORM ทำให้แล้ว)
7. **Cache** - Enable cache ใน production

## Tips

- ใช้ `php neo --help` เพื่อดู commands ทั้งหมด
- เปิด debug mode ตอน development: `APP_DEBUG=true`
- Sync schema หลังแก้ Entity: `php neo orm:sync --run`
- Clear cache หลังแก้ config: `php neo cache:clear`
- ใช้ Repository แทนการ query ตรงจาก Entity

## Troubleshooting

### Port 8000 ถูกใช้แล้ว

```bash
php -S localhost:8080 -t public/
```

### Database Connection Error

ตรวจสอบ `config/database.php` และสิทธิ์ MySQL

### Class Not Found

```bash
composer dump-autoload
```

### Template Not Found

ตรวจสอบ path ใน `config/view.php`

### Permission Denied

```bash
chmod -R 755 storage/
```
