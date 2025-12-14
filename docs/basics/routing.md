# Routing

NeoPhp's routing system provides a clean and expressive way to define your application's URL structure.

## Basic Routing

### Simple Routes

```php
use NeoPhp\Router\Router;

$router = new Router();

// GET route
$router->get('/users', [UserController::class, 'index']);

// POST route
$router->post('/users', [UserController::class, 'store']);

// PUT route
$router->put('/users/{id}', [UserController::class, 'update']);

// DELETE route
$router->delete('/users/{id}', [UserController::class, 'destroy']);

// PATCH route
$router->patch('/users/{id}', [UserController::class, 'patch']);
```

### Multiple HTTP Methods

```php
// Match multiple methods
$router->match(['GET', 'POST'], '/form', [FormController::class, 'handle']);

// Match any method
$router->any('/webhook', [WebhookController::class, 'handle']);
```

## Route Parameters

### Required Parameters

```php
$router->get('/users/{id}', function($id) {
    return "User ID: " . $id;
});

// Multiple parameters
$router->get('/posts/{category}/{slug}', function($category, $slug) {
    return "Category: $category, Slug: $slug";
});
```

### Optional Parameters

```php
$router->get('/users/{id?}', function($id = null) {
    if ($id) {
        return "User ID: " . $id;
    }
    return "All users";
});
```

### Parameter Constraints

```php
// Only match numeric IDs
$router->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '[0-9]+');

// Multiple constraints
$router->get('/posts/{category}/{id}', [PostController::class, 'show'])
    ->where([
        'category' => '[a-z]+',
        'id' => '[0-9]+'
    ]);

// Common patterns
$router->get('/users/{id}', [UserController::class, 'show'])
    ->whereNumber('id');

$router->get('/posts/{slug}', [PostController::class, 'show'])
    ->whereAlpha('slug');

$router->get('/articles/{slug}', [ArticleController::class, 'show'])
    ->whereAlphaNumeric('slug');

$router->get('/verify/{token}', [VerifyController::class, 'verify'])
    ->whereUuid('token');
```

## Route Groups

### Prefix Groups

```php
$router->prefix('/admin')->group(function($router) {
    // Matches /admin/dashboard
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
    
    // Matches /admin/users
    $router->get('/users', [AdminController::class, 'users']);
});
```

### Middleware Groups

```php
$router->middleware(['auth'])->group(function($router) {
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->get('/settings', [SettingsController::class, 'index']);
});

// Multiple middleware
$router->middleware(['auth', 'admin'])->group(function($router) {
    $router->get('/admin/dashboard', [AdminController::class, 'index']);
});
```

### Nested Groups

```php
$router->prefix('/api')->middleware(['api'])->group(function($router) {
    
    // Public API routes
    $router->get('/status', [ApiController::class, 'status']);
    
    // Authenticated API routes
    $router->middleware(['auth:api'])->group(function($router) {
        $router->get('/user', [ApiController::class, 'user']);
        
        // Admin API routes
        $router->middleware(['admin'])->prefix('/admin')->group(function($router) {
            $router->get('/users', [AdminApiController::class, 'users']);
        });
    });
});
```

## Named Routes

### Defining Named Routes

```php
$router->get('/profile', [ProfileController::class, 'show'])->name('profile');

$router->post('/users', [UserController::class, 'store'])->name('users.store');
```

### Generating URLs

```php
// Generate URL from route name
$url = route('profile'); // /profile

// With parameters
$url = route('users.show', ['id' => 1]); // /users/1

// With query parameters
$url = route('posts.index', ['page' => 2]); // /posts?page=2
```

## Resource Routes

### Basic Resource Routes

```php
// Creates all CRUD routes
$router->resource('/posts', PostController::class);

// Generated routes:
// GET    /posts              index
// GET    /posts/create       create
// POST   /posts              store
// GET    /posts/{id}         show
// GET    /posts/{id}/edit    edit
// PUT    /posts/{id}         update
// DELETE /posts/{id}         destroy
```

### API Resource Routes

```php
// Excludes create and edit (HTML forms)
$router->apiResource('/posts', PostController::class);

// Generated routes:
// GET    /posts         index
// POST   /posts         store
// GET    /posts/{id}    show
// PUT    /posts/{id}    update
// DELETE /posts/{id}    destroy
```

### Partial Resource Routes

```php
// Only specific actions
$router->resource('/photos', PhotoController::class)
    ->only(['index', 'show']);

// Exclude specific actions
$router->resource('/photos', PhotoController::class)
    ->except(['create', 'edit']);
```

### Nested Resources

```php
$router->resource('/posts/{post}/comments', CommentController::class);

// GET /posts/1/comments           index
// POST /posts/1/comments          store
// GET /posts/1/comments/2         show
// PUT /posts/1/comments/2         update
// DELETE /posts/1/comments/2      destroy
```

## Route Model Binding

### Implicit Binding

```php
$router->get('/users/{user}', function(User $user) {
    return response()->json($user);
});

// Automatically finds User by ID
```

### Custom Key

```php
$router->get('/posts/{post:slug}', function(Post $post) {
    return response()->json($post);
});

// Finds Post by slug instead of ID
```

## Redirects

```php
// Permanent redirect (301)
$router->redirect('/old-url', '/new-url', 301);

// Temporary redirect (302)
$router->redirect('/temp', '/destination');

// Named route redirect
$router->redirectToRoute('/old', 'new.route.name');
```

## Fallback Routes

```php
// 404 handler
$router->fallback(function() {
    return response()->json([
        'error' => 'Page not found'
    ], 404);
});
```

## Rate Limiting

```php
// Apply rate limit to route
$router->middleware(['throttle:60,1'])->group(function($router) {
    $router->post('/api/posts', [PostController::class, 'store']);
});

// Custom rate limits
$router->middleware(['throttle:api'])->group(function($router) {
    // Uses rate limit defined in config
});
```

## Route Caching

```bash
# Cache routes for production
php neo route:cache

# Clear route cache
php neo route:clear
```

## Advanced Usage

### Route Conditions

```php
$router->get('/users', [UserController::class, 'index'])
    ->where('id', '[0-9]+')
    ->middleware(['auth'])
    ->name('users.index');
```

### Route Macros

```php
// Define macro
Router::macro('apiResource', function($uri, $controller) {
    $this->resource($uri, $controller)->except(['create', 'edit']);
});

// Use macro
$router->apiResource('/posts', PostController::class);
```

### Subdomain Routing

```php
$router->domain('{account}.example.com')->group(function($router) {
    $router->get('/dashboard', function($account) {
        return "Dashboard for: $account";
    });
});
```

## Best Practices

1. **Use Named Routes** - Makes URL generation easier and refactoring safer
2. **Group Related Routes** - Keep routes organized
3. **Use Resource Routes** - For standard CRUD operations
4. **Apply Middleware at Group Level** - Reduce duplication
5. **Use Route Model Binding** - Simplify controller code
6. **Cache Routes in Production** - Improve performance
7. **Use API Resources for APIs** - Exclude unnecessary routes

## See Also

- [Controllers](controllers.md)
- [Middleware](middleware.md)
- [Requests & Responses](request-response.md)
- [API Development](../advanced/api.md)
