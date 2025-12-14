# Rate Limiting

Throttle requests to prevent abuse and brute-force attacks.

## Quick Start

### Basic Rate Limiting

```php
use NeoPhp\RateLimit\RateLimiter;

$router->middleware(['throttle:60,1'])->group(function($router) {
    $router->get('/api/posts', [PostController::class, 'index']);
});

// 60 requests per 1 minute per user
```

### Configuration

```php
// config/ratelimit.php
return [
    'default' => 'redis',
    
    'limiters' => [
        'api' => [
            'limit' => 60,
            'decay' => 60, // seconds
        ],
        'auth' => [
            'limit' => 5,
            'decay' => 60,
        ],
        'global' => [
            'limit' => 1000,
            'decay' => 60,
        ],
    ],
    
    'storage' => [
        'redis' => [
            'connection' => 'default',
        ],
        'cache' => [
            'store' => 'file',
        ],
    ],
];
```

## Middleware Usage

### Throttle Middleware

```php
// routes/api.php

// Basic throttling (60 requests per minute)
$router->middleware(['throttle:60,1'])->group(function($router) {
    $router->get('/posts', [PostController::class, 'index']);
});

// Named limiter
$router->middleware(['throttle:api'])->group(function($router) {
    $router->post('/posts', [PostController::class, 'store']);
});

// Per-user throttling
$router->middleware(['auth', 'throttle:100,1'])->group(function($router) {
    $router->get('/user/posts', [UserPostController::class, 'index']);
});
```

### Dynamic Limits

```php
$router->middleware(['throttle:rate_limit,1'])->group(function($router) {
    $router->get('/api/data', [ApiController::class, 'data']);
});

// In User model
public function rate_limit(): int
{
    return $this->isPremium() ? 1000 : 60;
}
```

## Manual Rate Limiting

### Using RateLimiter

```php
use NeoPhp\RateLimit\RateLimiter;

public function login(Request $request): Response
{
    $key = 'login:' . $request->ip();
    
    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        
        return response()->json([
            'error' => "Too many attempts. Try again in {$seconds} seconds."
        ], 429);
    }
    
    RateLimiter::hit($key, 60); // Decay in 60 seconds
    
    // Process login...
    
    if ($authenticated) {
        RateLimiter::clear($key);
    }
    
    return response()->json(['token' => $token]);
}
```

### Custom Keys

```php
// By IP address
$key = 'api:' . $request->ip();

// By user ID
$key = 'api:user:' . Auth::id();

// By email
$key = 'login:' . $request->email;

// By combination
$key = 'register:' . $request->ip() . ':' . $request->email;
```

## Response Headers

### Rate Limit Headers

```php
// Automatically added by middleware
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640000000

// When limit exceeded
HTTP/1.1 429 Too Many Requests
Retry-After: 42
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1640000042
```

### Custom Headers

```php
class ThrottleMiddleware
{
    public function handle($request, $next, $maxAttempts, $decayMinutes)
    {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }
        
        $this->limiter->hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }
}
```

## Different Limit Strategies

### Sliding Window

```php
class SlidingWindowLimiter
{
    public function allow(string $key, int $limit, int $window): bool
    {
        $now = time();
        $windowStart = $now - $window;
        
        // Remove old entries
        Redis::zRemRangeByScore($key, 0, $windowStart);
        
        // Count requests in window
        $count = Redis::zCard($key);
        
        if ($count < $limit) {
            Redis::zAdd($key, $now, $now);
            Redis::expire($key, $window);
            return true;
        }
        
        return false;
    }
}
```

### Token Bucket

```php
class TokenBucketLimiter
{
    public function allow(string $key, int $capacity, float $refillRate): bool
    {
        $now = microtime(true);
        
        $bucket = Redis::hGetAll($key) ?: [
            'tokens' => $capacity,
            'last_refill' => $now,
        ];
        
        // Refill tokens
        $elapsed = $now - $bucket['last_refill'];
        $tokensToAdd = $elapsed * $refillRate;
        $bucket['tokens'] = min($capacity, $bucket['tokens'] + $tokensToAdd);
        $bucket['last_refill'] = $now;
        
        if ($bucket['tokens'] >= 1) {
            $bucket['tokens']--;
            Redis::hMSet($key, $bucket);
            return true;
        }
        
        Redis::hMSet($key, $bucket);
        return false;
    }
}
```

## IP-Based Limiting

### Global IP Limit

```php
class IpRateLimitMiddleware
{
    public function handle($request, $next)
    {
        $ip = $request->ip();
        $key = "global:ip:{$ip}";
        
        if (RateLimiter::tooManyAttempts($key, 1000, 60)) {
            abort(429, 'Too many requests from your IP address');
        }
        
        RateLimiter::hit($key, 60);
        
        return $next($request);
    }
}
```

### Whitelist IPs

```php
class IpRateLimitMiddleware
{
    protected $whitelist = [
        '127.0.0.1',
        '192.168.1.100',
    ];
    
    public function handle($request, $next)
    {
        if (in_array($request->ip(), $this->whitelist)) {
            return $next($request);
        }
        
        // Apply rate limiting
        // ...
        
        return $next($request);
    }
}
```

## API Key Rate Limiting

### Per API Key

```php
class ApiKeyRateLimitMiddleware
{
    public function handle($request, $next)
    {
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey) {
            return response()->json(['error' => 'API key required'], 401);
        }
        
        $key = "api:key:{$apiKey}";
        $limit = $this->getApiKeyLimit($apiKey);
        
        if (RateLimiter::tooManyAttempts($key, $limit, 60)) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }
        
        RateLimiter::hit($key, 60);
        
        return $next($request);
    }
    
    protected function getApiKeyLimit(string $apiKey): int
    {
        // Get limit from database
        return ApiKey::where('key', $apiKey)->value('rate_limit') ?? 60;
    }
}
```

## Endpoint-Specific Limits

### Different Limits per Endpoint

```php
// routes/api.php

// Login endpoint - strict limit
$router->post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

// Registration - moderate limit
$router->post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:3,1');

// Read endpoints - generous limit
$router->get('/posts', [PostController::class, 'index'])
    ->middleware('throttle:100,1');

// Write endpoints - moderate limit
$router->post('/posts', [PostController::class, 'store'])
    ->middleware('throttle:30,1');
```

## Bypass Rate Limiting

### Skip for Admins

```php
class ThrottleMiddleware
{
    public function handle($request, $next, ...$limits)
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return $next($request);
        }
        
        // Apply rate limiting
        // ...
        
        return $next($request);
    }
}
```

## Custom Responses

### Custom Error Response

```php
class ThrottleMiddleware
{
    protected function buildTooManyAttemptsResponse($key, $maxAttempts)
    {
        $retryAfter = $this->limiter->availableIn($key);
        
        return response()->json([
            'message' => 'Too many requests',
            'retry_after' => $retryAfter,
            'retry_at' => now()->addSeconds($retryAfter)->toIso8601String(),
        ], 429)
        ->header('Retry-After', $retryAfter)
        ->header('X-RateLimit-Limit', $maxAttempts)
        ->header('X-RateLimit-Remaining', 0)
        ->header('X-RateLimit-Reset', time() + $retryAfter);
    }
}
```

## Testing Rate Limits

### Test Rate Limiting

```php
public function test_rate_limiting()
{
    // Make 60 requests (the limit)
    for ($i = 0; $i < 60; $i++) {
        $response = $this->get('/api/posts');
        $response->assertStatus(200);
    }
    
    // 61st request should be rate limited
    $response = $this->get('/api/posts');
    $response->assertStatus(429);
    $response->assertHeader('X-RateLimit-Remaining', 0);
}

public function test_rate_limit_resets()
{
    // Hit rate limit
    for ($i = 0; $i < 61; $i++) {
        $this->get('/api/posts');
    }
    
    // Travel forward in time
    $this->travel(61)->seconds();
    
    // Should work again
    $response = $this->get('/api/posts');
    $response->assertStatus(200);
}
```

## Best Practices

1. **Different Limits** - Use stricter limits for sensitive endpoints
2. **Per-User Limits** - Implement user-based rate limiting
3. **Clear Headers** - Always include rate limit headers
4. **Graceful Responses** - Provide helpful error messages
5. **Monitor Abuse** - Log and alert on repeated violations
6. **Whitelist Trusted** - Skip rate limiting for trusted IPs/keys
7. **Use Redis** - For better performance with high traffic
8. **Graduated Limits** - Increase limits for premium users
9. **Test Thoroughly** - Verify rate limiting works correctly
10. **Document Limits** - Clearly communicate limits to API consumers

## See Also

- [Authentication](authentication.md)
- [Security Best Practices](best-practices.md)
- [API Documentation](../advanced/api.md)
