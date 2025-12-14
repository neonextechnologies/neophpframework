# Permissions System

Granular permission management for fine-grained access control.

## Quick Start

### Define Permissions

```php
use App\Models\Permission;

Permission::create(['name' => 'create-posts']);
Permission::create(['name' => 'edit-posts']);
Permission::create(['name' => 'delete-posts']);
Permission::create(['name' => 'publish-posts']);
Permission::create(['name' => 'manage-comments']);
Permission::create(['name' => 'manage-users']);
```

### Assign to User

```php
$user = User::find(1);

// Assign single permission
$user->givePermissionTo('edit-posts');

// Assign multiple permissions
$user->givePermissionTo(['edit-posts', 'delete-posts']);

// Remove permission
$user->revokePermissionTo('delete-posts');

// Sync permissions
$user->syncPermissions(['create-posts', 'edit-posts']);
```

### Check Permissions

```php
// Check if user has permission
if ($user->hasPermissionTo('edit-posts')) {
    // User can edit posts
}

// Check multiple permissions (OR)
if ($user->hasAnyPermission(['edit-posts', 'delete-posts'])) {
    // User can edit OR delete
}

// Check multiple permissions (AND)
if ($user->hasAllPermissions(['edit-posts', 'delete-posts'])) {
    // User can edit AND delete
}
```

## Permission Structure

### Permission Model

```php
namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\ManyToMany;

#[Entity(table: 'permissions')]
class Permission
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string', unique: true)]
    public string $name;
    
    #[Column(type: 'string', nullable: true)]
    public ?string $guard_name = 'web';
    
    #[Column(type: 'string', nullable: true)]
    public ?string $display_name = null;
    
    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;
    
    #[Column(type: 'string', nullable: true)]
    public ?string $group = null;
    
    #[ManyToMany(target: Role::class, through: 'permission_role')]
    public array $roles = [];
    
    #[ManyToMany(target: User::class, through: 'permission_user')]
    public array $users = [];
}
```

### User Trait

```php
namespace App\Models;

trait HasPermissions
{
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
    
    public function hasPermissionTo(string $permission): bool
    {
        // Check direct permission
        if ($this->permissions->contains('name', $permission)) {
            return true;
        }
        
        // Check permission via roles
        foreach ($this->roles as $role) {
            if ($role->hasPermissionTo($permission)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }
        return false;
    }
    
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermissionTo($permission)) {
                return false;
            }
        }
        return true;
    }
    
    public function givePermissionTo(...$permissions): self
    {
        $permissions = collect($permissions)->flatten();
        
        foreach ($permissions as $permission) {
            $this->assignPermission($this->getPermission($permission));
        }
        
        return $this;
    }
    
    public function revokePermissionTo(...$permissions): self
    {
        $permissions = collect($permissions)->flatten();
        
        foreach ($permissions as $permission) {
            $this->removePermission($this->getPermission($permission));
        }
        
        return $this;
    }
    
    public function syncPermissions(...$permissions): self
    {
        $this->permissions()->sync(
            collect($permissions)->flatten()->map(function ($permission) {
                return $this->getPermission($permission)->id;
            })
        );
        
        return $this;
    }
    
    protected function getPermission($permission)
    {
        if (is_string($permission)) {
            return Permission::where('name', $permission)->firstOrFail();
        }
        
        return $permission;
    }
}
```

## Permission Groups

### Group Related Permissions

```php
// Create permissions with groups
Permission::create([
    'name' => 'create-posts',
    'group' => 'posts',
    'display_name' => 'Create Posts',
]);

Permission::create([
    'name' => 'edit-posts',
    'group' => 'posts',
    'display_name' => 'Edit Posts',
]);

Permission::create([
    'name' => 'manage-users',
    'group' => 'admin',
    'display_name' => 'Manage Users',
]);
```

### Assign Group

```php
public function givePermissionGroup(string $group): void
{
    $permissions = Permission::where('group', $group)->get();
    $this->givePermissionTo($permissions);
}

// Usage
$user->givePermissionGroup('posts');
```

### List by Group

```php
$grouped = Permission::all()->groupBy('group');

foreach ($grouped as $group => $permissions) {
    echo "Group: {$group}\n";
    foreach ($permissions as $permission) {
        echo "  - {$permission->display_name}\n";
    }
}
```

## Wildcard Permissions

### Use Wildcards

```php
// Give all post permissions
$user->givePermissionTo('posts.*');

// Give all admin permissions
$user->givePermissionTo('admin.*');

// Check wildcard
if ($user->hasPermissionTo('posts.create')) {
    // Will return true if user has 'posts.*'
}
```

### Implement Wildcards

```php
public function hasPermissionTo(string $permission): bool
{
    // Check exact match
    if ($this->permissions->contains('name', $permission)) {
        return true;
    }
    
    // Check wildcard patterns
    $parts = explode('.', $permission);
    
    for ($i = count($parts) - 1; $i > 0; $i--) {
        $wildcard = implode('.', array_slice($parts, 0, $i)) . '.*';
        if ($this->permissions->contains('name', $wildcard)) {
            return true;
        }
    }
    
    // Check super admin wildcard
    if ($this->permissions->contains('name', '*')) {
        return true;
    }
    
    return false;
}
```

## Middleware

### Permission Middleware

```php
namespace App\Middleware;

class PermissionMiddleware
{
    public function handle($request, $next, $permission)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }
        
        if (!Auth::user()->hasPermissionTo($permission)) {
            abort(403, 'You do not have permission to access this resource');
        }
        
        return $next($request);
    }
}
```

### Use in Routes

```php
// Single permission
$router->middleware(['permission:edit-posts'])->group(function($router) {
    $router->put('/posts/{post}', [PostController::class, 'update']);
});

// Multiple permissions (OR)
$router->middleware(['permission:edit-posts|delete-posts'])->group(function($router) {
    $router->get('/posts/manage', [PostController::class, 'manage']);
});

// Multiple permissions (AND)
$router->middleware(['permission:edit-posts,publish-posts'])->group(function($router) {
    $router->post('/posts/{post}/publish', [PostController::class, 'publish']);
});
```

## Blade Directives

### Check Permissions in Views

```blade
@can('edit-posts')
    <a href="/posts/{{ $post->id }}/edit">Edit</a>
@endcan

@cannot('delete-posts')
    <p>You cannot delete posts</p>
@endcannot

@canany(['edit-posts', 'delete-posts'])
    <button>Manage Post</button>
@endcanany

@hasPermission('publish-posts')
    <button>Publish</button>
@endhasPermission
```

## API Permissions

### Permission-Based API

```php
class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Post::query();
        
        // Filter based on permissions
        if (!$request->user()->hasPermissionTo('view-all-posts')) {
            $query->where('user_id', $request->user()->id);
        }
        
        if (!$request->user()->hasPermissionTo('view-draft-posts')) {
            $query->where('status', 'published');
        }
        
        return response()->json($query->get());
    }
    
    public function store(Request $request): Response
    {
        if (!$request->user()->hasPermissionTo('create-posts')) {
            return response()->json(['error' => 'No permission'], 403);
        }
        
        $post = Post::create($request->validated());
        
        return response()->json($post, 201);
    }
}
```

## Hierarchical Permissions

### Parent-Child Permissions

```php
class Permission extends Model
{
    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id');
    }
    
    public function getAllPermissions(): array
    {
        $permissions = [$this->name];
        
        foreach ($this->children as $child) {
            $permissions = array_merge($permissions, $child->getAllPermissions());
        }
        
        return $permissions;
    }
}

// Create hierarchy
$posts = Permission::create(['name' => 'posts']);
$createPost = Permission::create(['name' => 'posts.create', 'parent_id' => $posts->id]);
$editPost = Permission::create(['name' => 'posts.edit', 'parent_id' => $posts->id]);
```

## Scoped Permissions

### Resource-Specific Permissions

```php
// Permission with scope
Permission::create([
    'name' => 'edit-post',
    'scope' => 'own', // own, team, all
]);

// Check with scope
public function canEdit(User $user, Post $post): bool
{
    $permission = $user->permissions()
        ->where('name', 'edit-post')
        ->first();
    
    if (!$permission) {
        return false;
    }
    
    switch ($permission->scope) {
        case 'own':
            return $post->user_id === $user->id;
        case 'team':
            return $post->team_id === $user->team_id;
        case 'all':
            return true;
        default:
            return false;
    }
}
```

## Permission Caching

### Cache Permissions

```php
class User extends Model
{
    public function hasPermissionTo(string $permission): bool
    {
        $cacheKey = "user.{$this->id}.permissions";
        
        $permissions = Cache::remember($cacheKey, 3600, function () {
            return $this->getAllPermissions();
        });
        
        return in_array($permission, $permissions);
    }
    
    public function getAllPermissions(): array
    {
        $direct = $this->permissions->pluck('name')->toArray();
        
        $viaRoles = $this->roles
            ->flatMap(fn($role) => $role->permissions)
            ->pluck('name')
            ->toArray();
        
        return array_unique(array_merge($direct, $viaRoles));
    }
    
    public function clearPermissionCache(): void
    {
        Cache::forget("user.{$this->id}.permissions");
    }
}
```

## Testing

### Test Permissions

```php
public function test_user_can_create_post_with_permission()
{
    $user = User::factory()->create();
    $user->givePermissionTo('create-posts');
    
    $this->assertTrue($user->hasPermissionTo('create-posts'));
    
    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'Test Post',
    ]);
    
    $response->assertStatus(201);
}

public function test_user_cannot_create_post_without_permission()
{
    $user = User::factory()->create();
    
    $this->assertFalse($user->hasPermissionTo('create-posts'));
    
    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'Test Post',
    ]);
    
    $response->assertStatus(403);
}
```

## Best Practices

1. **Granular Permissions** - Create specific permissions for each action
2. **Clear Naming** - Use descriptive permission names (create-posts, not cp)
3. **Group Logically** - Organize permissions by feature
4. **Cache Checks** - Cache permission lookups for performance
5. **Use Policies** - Combine with policies for complex logic
6. **Document Permissions** - Maintain clear documentation
7. **Audit Trail** - Log permission changes
8. **Default Deny** - Deny access by default
9. **Test Coverage** - Test all permission scenarios
10. **Regular Review** - Audit and update permissions regularly

## See Also

- [Authorization](authorization.md)
- [RBAC](rbac.md)
- [Authentication](authentication.md)
- [Security Best Practices](best-practices.md)
