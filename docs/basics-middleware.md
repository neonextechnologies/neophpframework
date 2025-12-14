# Middleware

Filter HTTP requests entering your application.

## Creating Middleware

### Basic Middleware

```php
namespace App\Middleware;

use NeoPhp\Http\Request;
use Closure;

class CheckAge
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->get('age') < 18) {
            return redirect('/home');
        }
        
        return $next($request);
    }
}
```

### Before & After Middleware

```php
// Before middleware
class BeforeMiddleware
{
    public function handle($request, Closure $next)
    {
        // Perform action before request
        Log::info('Request started');
        
        return $next($request);
    }
}

// After middleware
class AfterMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Perform action after request
        Log::info('Request completed');
        
        return $response;
    }
}
```

## Registering Middleware

### Global Middleware

```php
// bootstrap/app.php
$app->middleware([
    \App\Middleware\TrustProxies::class,
    \App\Middleware\CheckForMaintenanceMode::class,
    \App\Middleware\ValidatePostSize::class,
    \App\Middleware\TrimStrings::class,
]);
```

### Route Middleware

```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    'auth' => \App\Middleware\Authenticate::class,
    'guest' => \App\Middleware\RedirectIfAuthenticated::class,
    'verified' => \App\Middleware\EnsureEmailIsVerified::class,
    'throttle' => \App\Middleware\ThrottleRequests::class,
];
```

## Applying Middleware

### To Routes

```php
// Single middleware
$router->get('/profile', [ProfileController::class, 'show'])
    ->middleware('auth');

// Multiple middleware
$router->get('/profile', [ProfileController::class, 'show'])
    ->middleware(['auth', 'verified']);

// Middleware groups
$router->middleware(['auth'])->group(function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/profile', [ProfileController::class, 'show']);
});
```

### To Controllers

```php
class UserController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified')->only(['edit', 'update']);
        $this->middleware('admin')->except(['index', 'show']);
    }
}
```

## Middleware Parameters

### Passing Parameters

```php
// Middleware
class RoleMiddleware
{
    public function handle($request, Closure $next, string $role)
    {
        if (!Auth::user()->hasRole($role)) {
            abort(403);
        }
        
        return $next($request);
    }
}

// Route
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware('role:admin');

// Multiple parameters
$router->get('/posts', [PostController::class, 'index'])
    ->middleware('role:admin,editor');
```

## Terminable Middleware

### After Response Sent

```php
namespace App\Middleware;

class TerminatingMiddleware
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
    
    public function terminate($request, $response)
    {
        // Perform action after response sent to browser
        // Clean up, log, etc.
        Log::info('Response sent', [
            'url' => $request->url(),
            'status' => $response->getStatusCode(),
        ]);
    }
}
```

## Common Middleware Examples

### Authentication

```php
class Authenticate
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }
        
        return $next($request);
    }
}
```

### CORS

```php
class Cors
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        return $response;
    }
}
```

### Request Logging

```php
class LogRequests
{
    public function handle($request, Closure $next)
    {
        Log::info('Request', [
            'method' => $request->method(),
            'url' => $request->url(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        return $next($request);
    }
}
```

### Maintenance Mode

```php
class CheckForMaintenanceMode
{
    public function handle($request, Closure $next)
    {
        if (app()->isDownForMaintenance()) {
            return response()->view('errors.503', [], 503);
        }
        
        return $next($request);
    }
}
```

## Middleware Groups

### Define Groups

```php
protected $middlewareGroups = [
    'web' => [
        \App\Middleware\EncryptCookies::class,
        \App\Middleware\AddQueuedCookiesToResponse::class,
        \App\Middleware\StartSession::class,
        \App\Middleware\VerifyCsrfToken::class,
    ],
    
    'api' => [
        'throttle:60,1',
        'auth:api',
    ],
];
```

### Use Groups

```php
$router->middleware(['web'])->group(function($router) {
    $router->get('/', [HomeController::class, 'index']);
});

$router->prefix('/api')->middleware(['api'])->group(function($router) {
    $router->get('/users', [ApiUserController::class, 'index']);
});
```

## Middleware Priority

### Set Priority

```php
protected $middlewarePriority = [
    \App\Middleware\StartSession::class,
    \App\Middleware\Authenticate::class,
    \App\Middleware\VerifyCsrfToken::class,
    \App\Middleware\Authorize::class,
];
```

## Best Practices

1. **Single Responsibility** - Each middleware should do one thing
2. **Reusable** - Make middleware reusable
3. **Order Matters** - Consider middleware execution order
4. **Performance** - Keep middleware lightweight
5. **Termination** - Use terminate() for cleanup tasks
6. **Parameters** - Use parameters for flexibility
7. **Testing** - Write tests for middleware

## See Also

- [Routing](routing.md)
- [Controllers](controllers.md)
- [Authentication](../security/authentication.md)
- [Authorization](../security/authorization.md)
