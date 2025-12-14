# JWT (JSON Web Tokens)

Stateless authentication using JWT tokens.

## Quick Start

### Configuration

```php
// config/jwt.php
return [
    'secret' => env('JWT_SECRET', 'your-secret-key'),
    'ttl' => env('JWT_TTL', 3600), // 1 hour
    'refresh_ttl' => env('JWT_REFRESH_TTL', 604800), // 7 days
    'algo' => 'HS256',
    'leeway' => 60,
    'blacklist_enabled' => true,
];
```

### Generate Token

```php
use NeoPhp\Auth\JWT;

public function login(Request $request): Response
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $credentials['email'])->first();

    if (!$user || !password_verify($credentials['password'], $user->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    $token = JWT::encode([
        'sub' => $user->id,
        'email' => $user->email,
        'name' => $user->name,
        'iat' => time(),
        'exp' => time() + config('jwt.ttl'),
    ]);

    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => config('jwt.ttl'),
    ]);
}
```

### Verify Token

```php
public function me(Request $request): Response
{
    try {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }
        
        $payload = JWT::decode($token);
        $user = User::find($payload['sub']);
        
        return response()->json($user);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Invalid token'], 401);
    }
}
```

## Token Structure

### Payload Claims

```php
$payload = [
    // Registered claims
    'iss' => 'https://yourdomain.com', // Issuer
    'sub' => $user->id,                 // Subject (user ID)
    'aud' => 'https://yourdomain.com', // Audience
    'exp' => time() + 3600,            // Expiration time
    'iat' => time(),                   // Issued at
    'nbf' => time(),                   // Not before
    'jti' => uniqid(),                 // JWT ID
    
    // Custom claims
    'email' => $user->email,
    'role' => $user->role,
    'permissions' => $user->permissions,
];

$token = JWT::encode($payload);
```

### Decode Token

```php
try {
    $payload = JWT::decode($token);
    
    // Access claims
    $userId = $payload['sub'];
    $email = $payload['email'];
    $expiresAt = $payload['exp'];
    
} catch (\Firebase\JWT\ExpiredException $e) {
    // Token expired
    return response()->json(['error' => 'Token expired'], 401);
    
} catch (\Firebase\JWT\SignatureInvalidException $e) {
    // Invalid signature
    return response()->json(['error' => 'Invalid signature'], 401);
    
} catch (\Exception $e) {
    // Other errors
    return response()->json(['error' => 'Invalid token'], 401);
}
```

## Refresh Tokens

### Generate Refresh Token

```php
public function login(Request $request): Response
{
    // ... authenticate user ...
    
    $accessToken = JWT::encode([
        'sub' => $user->id,
        'type' => 'access',
        'exp' => time() + config('jwt.ttl'),
    ]);
    
    $refreshToken = JWT::encode([
        'sub' => $user->id,
        'type' => 'refresh',
        'exp' => time() + config('jwt.refresh_ttl'),
        'jti' => uniqid(),
    ]);
    
    // Store refresh token
    RefreshToken::create([
        'user_id' => $user->id,
        'token' => hash('sha256', $refreshToken),
        'expires_at' => date('Y-m-d H:i:s', time() + config('jwt.refresh_ttl')),
    ]);
    
    return response()->json([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'token_type' => 'bearer',
        'expires_in' => config('jwt.ttl'),
    ]);
}
```

### Use Refresh Token

```php
public function refresh(Request $request): Response
{
    $refreshToken = $request->input('refresh_token');
    
    try {
        $payload = JWT::decode($refreshToken);
        
        // Verify token type
        if ($payload['type'] !== 'refresh') {
            return response()->json(['error' => 'Invalid token type'], 401);
        }
        
        // Check if token exists and not revoked
        $storedToken = RefreshToken::where('user_id', $payload['sub'])
            ->where('token', hash('sha256', $refreshToken))
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$storedToken) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }
        
        // Generate new access token
        $user = User::find($payload['sub']);
        $newAccessToken = JWT::encode([
            'sub' => $user->id,
            'type' => 'access',
            'exp' => time() + config('jwt.ttl'),
        ]);
        
        return response()->json([
            'access_token' => $newAccessToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl'),
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => 'Invalid refresh token'], 401);
    }
}
```

## Middleware

### JWT Auth Middleware

```php
namespace App\Middleware;

use NeoPhp\Auth\JWT;

class JwtAuthMiddleware
{
    public function handle($request, $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }
        
        try {
            $payload = JWT::decode($token);
            
            // Check blacklist
            if (config('jwt.blacklist_enabled')) {
                if (JWT::isBlacklisted($payload['jti'] ?? null)) {
                    return response()->json(['error' => 'Token revoked'], 401);
                }
            }
            
            // Load user
            $user = User::find($payload['sub']);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }
            
            // Attach to request
            $request->setUser($user);
            $request->setToken($payload);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
        
        return $next($request);
    }
}
```

### Protect Routes

```php
$router->middleware(['jwt.auth'])->prefix('/api')->group(function($router) {
    $router->get('/user', [UserController::class, 'profile']);
    $router->get('/posts', [PostController::class, 'index']);
});
```

## Token Blacklist

### Revoke Token

```php
public function logout(Request $request): Response
{
    $token = $request->bearerToken();
    $payload = JWT::decode($token);
    
    // Add to blacklist
    JWT::blacklist($payload['jti'], $payload['exp']);
    
    return response()->json(['message' => 'Logged out successfully']);
}
```

### Blacklist Implementation

```php
namespace NeoPhp\Auth;

use Illuminate\Support\Facades\Cache;

class JWT
{
    public static function blacklist(string $jti, int $exp): void
    {
        $ttl = $exp - time();
        
        if ($ttl > 0) {
            Cache::put("jwt:blacklist:{$jti}", true, $ttl);
        }
    }
    
    public static function isBlacklisted(?string $jti): bool
    {
        if (!$jti) {
            return false;
        }
        
        return Cache::has("jwt:blacklist:{$jti}");
    }
}
```

## Custom Claims

### Add Custom Data

```php
public function login(Request $request): Response
{
    $user = User::where('email', $request->email)->first();
    
    $token = JWT::encode([
        'sub' => $user->id,
        'email' => $user->email,
        'role' => $user->role,
        'permissions' => $user->permissions->pluck('name'),
        'subscription' => [
            'plan' => $user->subscription->plan,
            'expires_at' => $user->subscription->expires_at,
        ],
        'exp' => time() + 3600,
    ]);
    
    return response()->json(['token' => $token]);
}
```

### Validate Custom Claims

```php
public function handle($request, $next)
{
    $payload = JWT::decode($request->bearerToken());
    
    // Check subscription
    if (isset($payload['subscription'])) {
        $expiresAt = strtotime($payload['subscription']['expires_at']);
        if ($expiresAt < time()) {
            return response()->json(['error' => 'Subscription expired'], 403);
        }
    }
    
    return $next($request);
}
```

## Token Rotation

### Implement Rotation

```php
public function rotateToken(Request $request): Response
{
    $oldToken = $request->bearerToken();
    $payload = JWT::decode($oldToken);
    
    // Blacklist old token
    JWT::blacklist($payload['jti'], $payload['exp']);
    
    // Generate new token
    $newToken = JWT::encode([
        'sub' => $payload['sub'],
        'email' => $payload['email'],
        'role' => $payload['role'],
        'jti' => uniqid(),
        'exp' => time() + config('jwt.ttl'),
    ]);
    
    return response()->json([
        'access_token' => $newToken,
        'token_type' => 'bearer',
        'expires_in' => config('jwt.ttl'),
    ]);
}
```

## Multiple Secrets

### Use Different Secrets

```php
// config/jwt.php
return [
    'secrets' => [
        'web' => env('JWT_SECRET_WEB'),
        'mobile' => env('JWT_SECRET_MOBILE'),
        'api' => env('JWT_SECRET_API'),
    ],
];

// Encode with specific secret
$token = JWT::encode($payload, config('jwt.secrets.mobile'));

// Decode with specific secret
$payload = JWT::decode($token, config('jwt.secrets.mobile'));
```

## Token Scopes

### Add Scopes

```php
$token = JWT::encode([
    'sub' => $user->id,
    'scopes' => ['read:posts', 'write:posts', 'delete:posts'],
    'exp' => time() + 3600,
]);
```

### Check Scopes

```php
public function handle($request, $next)
{
    $payload = JWT::decode($request->bearerToken());
    $scopes = $payload['scopes'] ?? [];
    
    if (!in_array('write:posts', $scopes)) {
        return response()->json(['error' => 'Insufficient permissions'], 403);
    }
    
    return $next($request);
}
```

## Best Practices

1. **Use Strong Secrets** - Generate random 256-bit keys
2. **Short TTL** - Keep access token lifetime short (15-60 minutes)
3. **Use Refresh Tokens** - For longer sessions
4. **Implement Blacklist** - For logout and revocation
5. **Validate Claims** - Always verify exp, iat, nbf
6. **HTTPS Only** - Never transmit tokens over HTTP
7. **Secure Storage** - Store tokens securely on client
8. **Token Rotation** - Rotate tokens periodically
9. **Minimal Claims** - Don't store sensitive data in tokens
10. **Log Suspicious Activity** - Track token abuse

## See Also

- [Authentication](authentication.md)
- [Authorization](authorization.md)
- [Security Best Practices](best-practices.md)
- [API Documentation](../advanced/api.md)
