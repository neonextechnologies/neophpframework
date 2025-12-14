# REST API Development

Build powerful RESTful APIs with NeoPhp Framework.

## Quick Start

### Basic API Route

```php
// routes/api.php
$router->prefix('/api/v1')->group(function($router) {
    $router->get('/users', [Api\UserController::class, 'index']);
    $router->post('/users', [Api\UserController::class, 'store']);
    $router->get('/users/{id}', [Api\UserController::class, 'show']);
    $router->put('/users/{id}', [Api\UserController::class, 'update']);
    $router->delete('/users/{id}', [Api\UserController::class, 'destroy']);
});
```

### API Controller

```php
namespace App\Http\Controllers\Api;

use App\Models\User;
use NeoPhp\Http\Controller;
use NeoPhp\Http\Request;
use NeoPhp\Http\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::paginate(15);
        
        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => password_hash($validated['password'], PASSWORD_BCRYPT),
        ]);

        return response()->json($user, 201);
    }

    public function show(int $id): Response
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|min:3|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
        ]);

        $user->update($validated);
        
        return response()->json($user);
    }

    public function destroy(int $id): Response
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
```

## API Resources

Transform models into JSON responses with API Resources.

### Creating Resources

```php
namespace App\Http\Resources;

use NeoPhp\API\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'posts_count' => $this->posts->count(),
        ];
    }
}
```

### Using Resources

```php
use App\Http\Resources\UserResource;

public function show(int $id): Response
{
    $user = User::findOrFail($id);
    return new UserResource($user);
}

public function index(): Response
{
    $users = User::all();
    return UserResource::collection($users);
}
```

### Conditional Fields

```php
public function toArray(): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        'admin' => $this->when($this->is_admin, true),
        'secret' => $this->when(auth()->user()->isAdmin(), $this->secret),
    ];
}
```

### Relationships

```php
public function toArray(): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'posts' => PostResource::collection($this->whenLoaded('posts')),
        'latest_post' => new PostResource($this->whenLoaded('latestPost')),
    ];
}
```

## Authentication

### JWT Authentication

```php
use NeoPhp\Auth\JWT;

// Login
public function login(Request $request): Response
{
    $validated = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::findByEmail($validated['email']);
    
    if (!$user || !password_verify($validated['password'], $user->password)) {
        return response()->json([
            'error' => 'Invalid credentials'
        ], 401);
    }

    $token = JWT::encode([
        'user_id' => $user->id,
        'exp' => time() + (60 * 60 * 24) // 24 hours
    ]);

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
}

// Protected route
$router->middleware(['auth:jwt'])->group(function($router) {
    $router->get('/profile', [ProfileController::class, 'show']);
});
```

### API Token Authentication

```php
// Middleware
$router->middleware(['auth:api'])->group(function($router) {
    $router->get('/user', function() {
        return response()->json(auth()->user());
    });
});

// Send token in header
// Authorization: Bearer your-api-token-here
```

## Validation

### Request Validation

```php
public function store(Request $request): Response
{
    $validated = $request->validate([
        'name' => 'required|min:3|max:255',
        'email' => 'required|email|unique:users',
        'age' => 'integer|min:18|max:120',
        'role' => 'in:admin,user,guest',
    ]);

    // Validation passed
    $user = User::create($validated);
    return response()->json($user, 201);
}
```

### Custom Validation Messages

```php
$validated = $request->validate([
    'email' => 'required|email|unique:users',
], [
    'email.required' => 'Please provide an email address',
    'email.email' => 'Email must be valid',
    'email.unique' => 'This email is already taken',
]);
```

### Form Request Classes

```php
namespace App\Http\Requests;

use NeoPhp\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.unique' => 'Email already exists',
        ];
    }
}

// Controller
public function store(StoreUserRequest $request): Response
{
    $validated = $request->validated();
    // ...
}
```

## Error Handling

### Standard Error Responses

```php
// 404 Not Found
return response()->json([
    'error' => 'Resource not found'
], 404);

// 422 Validation Error
return response()->json([
    'error' => 'Validation failed',
    'errors' => [
        'email' => ['Email is required'],
        'password' => ['Password must be at least 8 characters'],
    ]
], 422);

// 500 Server Error
return response()->json([
    'error' => 'Internal server error',
    'message' => 'Something went wrong'
], 500);
```

### Exception Handler

```php
namespace App\Exceptions;

use NeoPhp\Exceptions\Handler;

class ApiExceptionHandler extends Handler
{
    public function render($exception): Response
    {
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'error' => 'Resource not found'
            ], 404);
        }

        if ($exception instanceof ValidationException) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $exception->errors()
            ], 422);
        }

        return response()->json([
            'error' => 'Server error',
            'message' => $exception->getMessage()
        ], 500);
    }
}
```

## Pagination

### Basic Pagination

```php
public function index(Request $request): Response
{
    $perPage = $request->query('per_page', 15);
    $users = User::paginate($perPage);

    return response()->json([
        'data' => $users->items(),
        'meta' => [
            'current_page' => $users->currentPage(),
            'from' => $users->firstItem(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'to' => $users->lastItem(),
            'total' => $users->total(),
        ],
        'links' => [
            'first' => $users->url(1),
            'last' => $users->url($users->lastPage()),
            'prev' => $users->previousPageUrl(),
            'next' => $users->nextPageUrl(),
        ]
    ]);
}
```

### Cursor Pagination

```php
public function index(): Response
{
    $users = User::cursorPaginate(15);

    return response()->json([
        'data' => $users->items(),
        'next_cursor' => $users->nextCursor(),
        'prev_cursor' => $users->previousCursor(),
    ]);
}
```

## Filtering & Sorting

### Query Parameters

```php
public function index(Request $request): Response
{
    $query = User::query();

    // Filtering
    if ($request->has('status')) {
        $query->where('status', $request->query('status'));
    }

    if ($request->has('search')) {
        $search = $request->query('search');
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // Sorting
    $sortBy = $request->query('sort_by', 'created_at');
    $sortOrder = $request->query('sort_order', 'desc');
    $query->orderBy($sortBy, $sortOrder);

    // Pagination
    $users = $query->paginate(15);

    return response()->json($users);
}
```

## Rate Limiting

### Apply Rate Limits

```php
// 60 requests per minute
$router->middleware(['throttle:60,1'])->group(function($router) {
    $router->post('/api/posts', [PostController::class, 'store']);
});

// Custom rate limit
$router->middleware(['throttle:api'])->group(function($router) {
    $router->get('/api/users', [UserController::class, 'index']);
});
```

### Rate Limit Response

```php
return response()->json([
    'error' => 'Too many requests',
    'retry_after' => 60
], 429);
```

## Versioning

### URL Versioning

```php
// v1 routes
$router->prefix('/api/v1')->group(function($router) {
    $router->get('/users', [V1\UserController::class, 'index']);
});

// v2 routes
$router->prefix('/api/v2')->group(function($router) {
    $router->get('/users', [V2\UserController::class, 'index']);
});
```

### Header Versioning

```php
$router->middleware(['api.version'])->group(function($router) {
    $router->get('/users', [UserController::class, 'index']);
});

// Client sends: Accept: application/vnd.api.v2+json
```

## CORS

### Enable CORS

```php
// Middleware
$router->middleware(['cors'])->prefix('/api')->group(function($router) {
    $router->get('/users', [UserController::class, 'index']);
});

// config/cors.php
return [
    'allowed_origins' => ['*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

## Best Practices

1. **Use API Resources** - Transform models consistently
2. **Version Your API** - Plan for future changes
3. **Use HTTP Status Codes** - Follow REST conventions
4. **Implement Rate Limiting** - Protect your API
5. **Document Your API** - Use OpenAPI/Swagger
6. **Validate Input** - Always validate incoming data
7. **Handle Errors Gracefully** - Return meaningful error messages
8. **Use Authentication** - Secure sensitive endpoints
9. **Paginate Results** - Don't return all records at once
10. **Enable CORS** - For cross-origin requests

## See Also

- [Authentication](../security/authentication.md)
- [JWT Tokens](../security/jwt.md)
- [Rate Limiting](../security/rate-limiting.md)
- [Testing APIs](../testing/http-tests.md)
