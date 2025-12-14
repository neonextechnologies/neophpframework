# Role-Based Access Control (RBAC)

Complete role and permission management system.

## Quick Start

### Database Schema

```php
// database/migrations/create_rbac_tables.php
Schema::create('roles', function ($table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('display_name')->nullable();
    $table->text('description')->nullable();
    $table->timestamps();
});

Schema::create('permissions', function ($table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('display_name')->nullable();
    $table->text('description')->nullable();
    $table->timestamps();
});

Schema::create('role_user', function ($table) {
    $table->id();
    $table->foreignId('role_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamps();
    
    $table->unique(['role_id', 'user_id']);
});

Schema::create('permission_role', function ($table) {
    $table->id();
    $table->foreignId('permission_id')->constrained()->onDelete('cascade');
    $table->foreignId('role_id')->constrained()->onDelete('cascade');
    $table->timestamps();
    
    $table->unique(['permission_id', 'role_id']);
});
```

### Models

```php
// app/Models/Role.php
namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\ManyToMany;

#[Entity(table: 'roles')]
class Role
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $name;
    
    #[Column(type: 'string', nullable: true)]
    public ?string $display_name = null;
    
    #[ManyToMany(target: Permission::class, through: 'permission_role')]
    public array $permissions = [];
    
    #[ManyToMany(target: User::class, through: 'role_user')]
    public array $users = [];
}

// app/Models/Permission.php
#[Entity(table: 'permissions')]
class Permission
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $name;
    
    #[Column(type: 'string', nullable: true)]
    public ?string $display_name = null;
    
    #[ManyToMany(target: Role::class, through: 'permission_role')]
    public array $roles = [];
}
```

### User Trait

```php
// app/Models/User.php
namespace App\Models;

use NeoPhp\Auth\Traits\HasRoles;

class User extends Model
{
    use HasRoles;
    
    #[ManyToMany(target: Role::class, through: 'role_user')]
    public array $roles = [];
}
```

## Assigning Roles

### Assign Single Role

```php
$user = User::find(1);

// Assign role by name
$user->assignRole('admin');

// Assign role by ID
$user->assignRole(1);

// Assign role object
$role = Role::where('name', 'editor')->first();
$user->assignRole($role);
```

### Assign Multiple Roles

```php
$user->assignRole(['admin', 'editor']);

// Or
$user->assignRole('admin', 'editor');
```

### Remove Roles

```php
// Remove single role
$user->removeRole('editor');

// Remove multiple roles
$user->removeRole(['editor', 'moderator']);

// Remove all roles
$user->syncRoles([]);
```

### Sync Roles

```php
// Replace all roles with new set
$user->syncRoles(['admin', 'editor']);
```

## Checking Roles

### Has Role

```php
// Check single role
if ($user->hasRole('admin')) {
    // User is admin
}

// Check multiple roles (OR logic)
if ($user->hasAnyRole(['admin', 'editor'])) {
    // User is admin OR editor
}

// Check multiple roles (AND logic)
if ($user->hasAllRoles(['admin', 'editor'])) {
    // User is both admin AND editor
}
```

### In Middleware

```php
// Protect route with role
$router->middleware(['role:admin'])->group(function($router) {
    $router->get('/admin/dashboard', [AdminController::class, 'index']);
});

// Multiple roles (OR)
$router->middleware(['role:admin|editor'])->group(function($router) {
    $router->get('/posts/manage', [PostController::class, 'manage']);
});

// Multiple roles (AND)
$router->middleware(['role:admin,editor'])->group(function($router) {
    $router->get('/restricted', [AdminController::class, 'restricted']);
});
```

### In Controllers

```php
public function __construct()
{
    $this->middleware('role:admin');
}

public function index()
{
    // Check role in method
    if (!auth()->user()->hasRole('admin')) {
        abort(403);
    }
    
    return view('admin.dashboard');
}
```

### In Blade Templates

```php
@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole

@hasanyrole('admin|editor')
    <a href="/posts/create">Create Post</a>
@endhasanyrole

@hasallroles('admin,editor')
    <a href="/restricted">Restricted Area</a>
@endhasallroles
```

## Managing Permissions

### Create Permissions

```php
use App\Models\Permission;

// Create permission
Permission::create([
    'name' => 'edit-posts',
    'display_name' => 'Edit Posts',
    'description' => 'Can edit all posts',
]);

Permission::create(['name' => 'delete-posts']);
Permission::create(['name' => 'publish-posts']);
Permission::create(['name' => 'manage-users']);
```

### Assign Permissions to Role

```php
$role = Role::where('name', 'editor')->first();

// Assign single permission
$role->givePermissionTo('edit-posts');

// Assign multiple permissions
$role->givePermissionTo(['edit-posts', 'delete-posts']);

// Or
$role->syncPermissions(['edit-posts', 'delete-posts', 'publish-posts']);
```

### Assign Permissions to User

```php
// Direct permission (bypass roles)
$user->givePermissionTo('edit-posts');

// Remove permission
$user->revokePermissionTo('edit-posts');

// Sync permissions
$user->syncPermissions(['edit-posts', 'delete-posts']);
```

## Checking Permissions

### Has Permission

```php
// Check if user has permission (via role or direct)
if ($user->hasPermissionTo('edit-posts')) {
    // Can edit posts
}

// Check multiple permissions (OR)
if ($user->hasAnyPermission(['edit-posts', 'delete-posts'])) {
    // Can edit OR delete
}

// Check multiple permissions (AND)
if ($user->hasAllPermissions(['edit-posts', 'delete-posts'])) {
    // Can edit AND delete
}

// Check via role
if ($user->hasRole('editor') && $user->can('edit-posts')) {
    // Is editor and can edit posts
}
```

### In Middleware

```php
// Protect with permission
$router->middleware(['permission:edit-posts'])->group(function($router) {
    $router->get('/posts/edit', [PostController::class, 'edit']);
});

// Multiple permissions (OR)
$router->middleware(['permission:edit-posts|delete-posts'])->group(function($router) {
    $router->get('/posts/manage', [PostController::class, 'manage']);
});
```

### In Controllers

```php
public function edit(Post $post)
{
    $this->authorize('edit-posts');
    
    return view('posts.edit', compact('post'));
}

// Or using can()
public function delete(Post $post)
{
    if (!auth()->user()->can('delete-posts')) {
        abort(403, 'You do not have permission to delete posts');
    }
    
    $post->delete();
    return redirect('/posts');
}
```

### In Blade Templates

```php
@can('edit-posts')
    <a href="/posts/{{ $post->id }}/edit">Edit</a>
@endcan

@cannot('delete-posts')
    <p>You cannot delete posts</p>
@endcannot
```

## Role Hierarchy

### Define Hierarchy

```php
// config/permission.php
return [
    'roles' => [
        'super-admin' => [
            'level' => 100,
            'inherits' => [],
        ],
        'admin' => [
            'level' => 80,
            'inherits' => ['editor', 'moderator'],
        ],
        'editor' => [
            'level' => 60,
            'inherits' => ['author'],
        ],
        'author' => [
            'level' => 40,
            'inherits' => [],
        ],
        'moderator' => [
            'level' => 50,
            'inherits' => [],
        ],
    ],
];
```

### Check Hierarchy

```php
class User extends Model
{
    public function hasRoleLevel(int $level): bool
    {
        $userLevel = $this->getRoleLevel();
        return $userLevel >= $level;
    }
    
    public function getRoleLevel(): int
    {
        $roles = config('permission.roles');
        $maxLevel = 0;
        
        foreach ($this->roles as $role) {
            if (isset($roles[$role->name]['level'])) {
                $level = $roles[$role->name]['level'];
                if ($level > $maxLevel) {
                    $maxLevel = $level;
                }
            }
        }
        
        return $maxLevel;
    }
}
```

## Permission Groups

### Group Permissions

```php
// config/permission.php
return [
    'groups' => [
        'posts' => [
            'view-posts',
            'create-posts',
            'edit-posts',
            'delete-posts',
            'publish-posts',
        ],
        'users' => [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
        ],
    ],
];
```

### Assign Group

```php
class Role extends Model
{
    public function givePermissionGroup(string $group): void
    {
        $permissions = config("permission.groups.{$group}", []);
        $this->givePermissionTo($permissions);
    }
}

// Usage
$role = Role::where('name', 'editor')->first();
$role->givePermissionGroup('posts');
```

## Wildcard Permissions

### Define Wildcards

```php
// Give all post permissions
$role->givePermissionTo('posts.*');

// Check wildcard
if ($user->hasPermissionTo('posts.*')) {
    // Has all post permissions
}

// Implementation
class User extends Model
{
    public function hasPermissionTo(string $permission): bool
    {
        // Check exact match
        if ($this->permissions->contains('name', $permission)) {
            return true;
        }
        
        // Check wildcard
        $parts = explode('.', $permission);
        if (count($parts) > 1) {
            $wildcard = $parts[0] . '.*';
            if ($this->permissions->contains('name', $wildcard)) {
                return true;
            }
        }
        
        return false;
    }
}
```

## Super Admin

### Grant All Permissions

```php
// app/Providers/AuthServiceProvider.php
use NeoPhp\Auth\Gate;

Gate::before(function ($user, $ability) {
    if ($user->hasRole('super-admin')) {
        return true;
    }
});
```

### Revoke Super Admin

```php
$user->removeRole('super-admin');
```

## Role & Permission API

### REST Endpoints

```php
// routes/api.php

// Roles
$router->get('/roles', [RoleController::class, 'index']);
$router->post('/roles', [RoleController::class, 'store']);
$router->get('/roles/{role}', [RoleController::class, 'show']);
$router->put('/roles/{role}', [RoleController::class, 'update']);
$router->delete('/roles/{role}', [RoleController::class, 'destroy']);

// Permissions
$router->get('/permissions', [PermissionController::class, 'index']);
$router->post('/permissions', [PermissionController::class, 'store']);

// Assign
$router->post('/users/{user}/roles', [UserRoleController::class, 'store']);
$router->delete('/users/{user}/roles/{role}', [UserRoleController::class, 'destroy']);
```

### Controller Implementation

```php
class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::with('permissions')->get();
        return response()->json($roles);
    }
    
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles',
            'display_name' => 'nullable|string',
            'permissions' => 'array',
        ]);
        
        $role = Role::create($validated);
        
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }
        
        return response()->json($role, 201);
    }
}
```

## Best Practices

1. **Use Clear Names** - Use descriptive permission names (edit-posts, not ep)
2. **Group Related Permissions** - Organize by feature (posts.*, users.*)
3. **Minimize Roles** - Keep role count manageable
4. **Cache Permissions** - Cache permission checks for performance
5. **Audit Changes** - Log role/permission changes
6. **Test Thoroughly** - Write tests for all permission scenarios
7. **Document Roles** - Maintain role/permission documentation
8. **Use Seeders** - Seed default roles/permissions
9. **Validate Assignments** - Prevent invalid role/permission assignments
10. **Regular Review** - Audit roles/permissions regularly

## See Also

- [Authentication](authentication.md)
- [Authorization](authorization.md)
- [Permissions](permissions.md)
- [Security Best Practices](best-practices.md)
