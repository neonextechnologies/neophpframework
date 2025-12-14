# Authorization

Permission-based access control for resources.

## Quick Start

### Define Gates

```php
// app/Providers/AuthServiceProvider.php
use NeoPhp\Auth\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('view-post', function ($user, $post) {
            return $user->id === $post->user_id;
        });

        Gate::define('update-post', function ($user, $post) {
            return $user->id === $post->user_id;
        });

        Gate::define('delete-post', function ($user, $post) {
            return $user->id === $post->user_id || $user->isAdmin();
        });

        Gate::define('manage-users', function ($user) {
            return $user->isAdmin();
        });
    }
}
```

### Check Authorization

```php
use NeoPhp\Auth\Gate;

public function update(Request $request, Post $post): Response
{
    if (!Gate::allows('update-post', $post)) {
        abort(403, 'Unauthorized');
    }

    $post->update($request->validated());
    
    return response()->json($post);
}

// Alternative syntax
if (Gate::denies('update-post', $post)) {
    abort(403);
}

// Multiple arguments
Gate::define('update-comment', function ($user, $post, $comment) {
    return $user->id === $comment->user_id;
});
```

## Policies

### Create Policy

```php
namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        return $post->published || $user->id === $post->user_id;
    }

    public function create(User $user): bool
    {
        return $user->email_verified_at !== null;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }
}
```

### Register Policy

```php
// app/Providers/AuthServiceProvider.php
use App\Models\Post;
use App\Policies\PostPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Post::class => PostPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
```

### Use Policy

```php
use NeoPhp\Auth\Gate;

public function update(Request $request, Post $post): Response
{
    $this->authorize('update', $post);
    
    $post->update($request->validated());
    
    return response()->json($post);
}

// Alternative
if (!Gate::allows('update', $post)) {
    abort(403);
}

// Check multiple policies
if (Gate::any(['update', 'delete'], $post)) {
    // User can update OR delete
}

if (Gate::none(['update', 'delete'], $post)) {
    // User cannot update AND cannot delete
}
```

## Middleware Authorization

### Protect Routes

```php
// Using authorize middleware
$router->middleware(['auth', 'can:manage-users'])->group(function($router) {
    $router->get('/admin/users', [AdminController::class, 'users']);
});

// With parameters
$router->put('/posts/{post}', [PostController::class, 'update'])
    ->middleware('can:update,post');

// Multiple permissions
$router->middleware(['can:update,post|delete,post'])->group(function($router) {
    $router->delete('/posts/{post}', [PostController::class, 'destroy']);
});
```

## Controller Authorization

### Authorize in Constructor

```php
class PostController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Post::class, 'post');
    }

    // Automatically checks:
    // index -> viewAny
    // show -> view
    // create -> create
    // store -> create
    // edit -> update
    // update -> update
    // destroy -> delete
}
```

### Manual Authorization

```php
public function update(Request $request, Post $post): Response
{
    // Throws 403 if not authorized
    $this->authorize('update', $post);
    
    $post->update($request->validated());
    
    return response()->json($post);
}

// With custom message
$this->authorize('update', $post, 'You cannot update this post.');

// With custom response
try {
    $this->authorize('update', $post);
} catch (AuthorizationException $e) {
    return response()->json(['error' => 'Access denied'], 403);
}
```

## Guest Users

```php
Gate::define('view-post', function (?User $user, Post $post) {
    // Allow guests to view published posts
    if ($post->published) {
        return true;
    }
    
    // Require authentication for drafts
    return $user && $user->id === $post->user_id;
});
```

## Before & After Hooks

### Before Hook

```php
// Check before all other authorization
Gate::before(function ($user, $ability) {
    if ($user->isAdmin()) {
        return true; // Admin can do everything
    }
});

// In Policy
class PostPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        return null; // Continue to policy methods
    }
}
```

### After Hook

```php
Gate::after(function ($user, $ability, $result, $arguments) {
    // Log authorization attempts
    Log::info('Authorization', [
        'user' => $user->id,
        'ability' => $ability,
        'result' => $result,
    ]);
});
```

## Inline Authorization

### Check in Blade Templates

```php
@can('update', $post)
    <a href="/posts/{{ $post->id }}/edit">Edit</a>
@endcan

@cannot('delete', $post)
    <p>You cannot delete this post</p>
@endcannot

@canany(['update', 'delete'], $post)
    <button>Actions</button>
@endcanany
```

### Check in Models

```php
class Post extends Model
{
    public function canBeEdited(User $user): bool
    {
        return Gate::forUser($user)->allows('update', $this);
    }
}
```

## Resource Authorization

### Check Multiple Resources

```php
$posts = Post::all();

$editablePosts = $posts->filter(function ($post) {
    return Gate::allows('update', $post);
});
```

### Authorize Collection

```php
public function bulkDelete(Request $request): Response
{
    $postIds = $request->input('post_ids');
    $posts = Post::whereIn('id', $postIds)->get();
    
    foreach ($posts as $post) {
        if (Gate::denies('delete', $post)) {
            return response()->json([
                'error' => "Cannot delete post {$post->id}"
            ], 403);
        }
    }
    
    Post::whereIn('id', $postIds)->delete();
    
    return response()->json(['message' => 'Posts deleted']);
}
```

## Authorization Responses

### Custom Messages

```php
Gate::define('update-post', function ($user, $post) {
    return $user->id === $post->user_id
        ? Response::allow()
        : Response::deny('You do not own this post.');
});

// Use in controller
$response = Gate::inspect('update-post', $post);

if ($response->denied()) {
    return response()->json(['error' => $response->message()], 403);
}
```

### Authorization Response

```php
use NeoPhp\Auth\Response;

Gate::define('delete-post', function ($user, $post) {
    if (!$post->canBeDeleted()) {
        return Response::deny('Post has comments and cannot be deleted.');
    }
    
    return $user->id === $post->user_id
        ? Response::allow()
        : Response::deny('You are not the author.');
});
```

## Complex Authorization

### Multiple Conditions

```php
Gate::define('publish-post', function ($user, $post) {
    // Must be author
    if ($user->id !== $post->user_id) {
        return false;
    }
    
    // Must have verified email
    if (!$user->email_verified_at) {
        return false;
    }
    
    // Must not be banned
    if ($user->banned_at) {
        return false;
    }
    
    // Post must be complete
    if (strlen($post->content) < 100) {
        return false;
    }
    
    return true;
});
```

### Relationship-Based

```php
Gate::define('comment-on-post', function ($user, $post) {
    // Cannot comment on own posts
    if ($user->id === $post->user_id) {
        return false;
    }
    
    // Cannot comment if blocked by author
    if ($post->user->hasBlocked($user)) {
        return false;
    }
    
    // Can comment if post allows comments
    return $post->allows_comments;
});
```

## Authorization Events

### Listen to Authorization

```php
use NeoPhp\Auth\Events\GateEvaluated;

Event::listen(GateEvaluated::class, function ($event) {
    if ($event->result === false) {
        Log::warning('Unauthorized access attempt', [
            'user' => $event->user->id,
            'ability' => $event->ability,
            'arguments' => $event->arguments,
        ]);
    }
});
```

## Testing Authorization

### Test Gates

```php
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_user_can_update_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        
        $this->assertTrue(Gate::forUser($user)->allows('update', $post));
    }
    
    public function test_user_cannot_update_others_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        $this->assertTrue(Gate::forUser($user)->denies('update', $post));
    }
}
```

### Test Policies

```php
public function test_admin_can_delete_any_post()
{
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->create();
    
    $this->assertTrue($admin->can('delete', $post));
}

public function test_regular_user_cannot_force_delete()
{
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    
    $this->assertFalse($user->can('forceDelete', $post));
}
```

## Best Practices

1. **Use Policies** - For model-based authorization
2. **Use Gates** - For general permissions
3. **Authorize Early** - Check permissions before processing
4. **Consistent Naming** - Use standard CRUD names (view, create, update, delete)
5. **Clear Messages** - Provide helpful error messages
6. **Test Authorization** - Write tests for all permissions
7. **Cache Checks** - Cache expensive authorization checks
8. **Log Denials** - Track unauthorized access attempts
9. **Graceful Failures** - Handle authorization failures properly
10. **Document Permissions** - Maintain permission documentation

## See Also

- [Authentication](authentication.md)
- [RBAC](rbac.md)
- [Permissions](permissions.md)
- [Security Best Practices](best-practices.md)
