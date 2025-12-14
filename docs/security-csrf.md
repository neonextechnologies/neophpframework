# CSRF Protection

Cross-Site Request Forgery protection for forms and AJAX requests.

## Quick Start

### Enable CSRF Middleware

```php
// bootstrap/app.php
$app->middleware([
    \NeoPhp\Security\CsrfMiddleware::class,
]);
```

### Form Protection

```blade
<form method="POST" action="/profile">
    @csrf
    
    <input type="text" name="name" value="{{ $user->name }}">
    <button type="submit">Update Profile</button>
</form>
```

### Generated HTML

```html
<form method="POST" action="/profile">
    <input type="hidden" name="_token" value="kR3jFm8sP...">
    
    <input type="text" name="name" value="John Doe">
    <button type="submit">Update Profile</button>
</form>
```

## AJAX Protection

### Send Token in Header

```javascript
// Get token from meta tag
const token = document.querySelector('meta[name="csrf-token"]').content;

// Send with fetch
fetch('/api/posts', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
    },
    body: JSON.stringify({
        title: 'New Post',
        content: 'Post content...'
    })
});

// Send with Axios
axios.defaults.headers.common['X-CSRF-TOKEN'] = token;

axios.post('/api/posts', {
    title: 'New Post',
    content: 'Post content...'
});
```

### Meta Tag

```blade
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <!-- Your content -->
</body>
</html>
```

## Token Generation

### Get Current Token

```php
// In controller
$token = csrf_token();

// In middleware
$token = $request->session()->token();

// Generate new token
$token = Str::random(40);
$request->session()->put('_token', $token);
```

### Regenerate Token

```php
public function logout(Request $request): Response
{
    Auth::logout();
    
    // Regenerate CSRF token
    $request->session()->regenerateToken();
    
    return redirect('/');
}
```

## Exclude Routes

### Exclude from CSRF

```php
// app/Middleware/CsrfMiddleware.php
class CsrfMiddleware extends BaseCsrfMiddleware
{
    protected $except = [
        '/api/*',
        '/webhook/*',
        '/stripe/webhook',
        '/paypal/ipn',
    ];
}
```

### Exclude Specific Routes

```php
$router->post('/webhook', [WebhookController::class, 'handle'])
    ->withoutMiddleware(CsrfMiddleware::class);
```

## Token Validation

### Manual Validation

```php
public function store(Request $request): Response
{
    // Validate CSRF token
    if (!csrf_verify($request->input('_token'))) {
        abort(419, 'CSRF token mismatch');
    }
    
    // Process request
    return response()->json(['success' => true]);
}
```

### Custom Validation

```php
use NeoPhp\Security\Csrf;

public function handle($request, $next)
{
    $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
    
    if (!Csrf::verify($token, $request->session()->token())) {
        return response()->json(['error' => 'Invalid CSRF token'], 419);
    }
    
    return $next($request);
}
```

## Double Submit Cookie

### Implementation

```php
class DoubleCsrfMiddleware
{
    public function handle($request, $next)
    {
        // Set CSRF cookie
        if (!$request->hasCookie('XSRF-TOKEN')) {
            $token = Str::random(40);
            cookie()->queue('XSRF-TOKEN', $token, 120, '/', null, true, false);
        }
        
        // Verify on POST/PUT/DELETE
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $cookieToken = $request->cookie('XSRF-TOKEN');
            $headerToken = $request->header('X-XSRF-TOKEN');
            
            if ($cookieToken !== $headerToken) {
                abort(419, 'CSRF token mismatch');
            }
        }
        
        return $next($request);
    }
}
```

### Client-Side

```javascript
// Automatically read XSRF-TOKEN cookie and send as X-XSRF-TOKEN header
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

axios.post('/api/posts', {
    title: 'New Post'
});
```

## SameSite Cookies

### Configure Cookies

```php
// config/session.php
return [
    'cookie' => env('SESSION_COOKIE', 'neophp_session'),
    'same_site' => 'lax', // strict, lax, or none
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
];
```

### SameSite Options

```php
// Strict - Cookie only sent for same-site requests
'same_site' => 'strict',

// Lax - Cookie sent for top-level navigation
'same_site' => 'lax',

// None - Cookie sent for all requests (requires secure)
'same_site' => 'none',
'secure' => true,
```

## Token Lifetime

### Configure TTL

```php
// config/csrf.php
return [
    'ttl' => 3600, // 1 hour
    'regenerate' => true, // Regenerate after successful validation
];
```

### Store Token with Expiry

```php
class Csrf
{
    public static function generate(): string
    {
        $token = Str::random(40);
        $expiry = time() + config('csrf.ttl');
        
        session()->put('_token', $token);
        session()->put('_token_expiry', $expiry);
        
        return $token;
    }
    
    public static function verify(string $token): bool
    {
        $sessionToken = session()->get('_token');
        $expiry = session()->get('_token_expiry');
        
        // Check expiry
        if ($expiry < time()) {
            return false;
        }
        
        // Compare tokens
        return hash_equals($sessionToken, $token);
    }
}
```

## Multi-Step Forms

### Store Token for Multiple Requests

```php
public function startForm(Request $request): Response
{
    $formToken = Str::random(40);
    
    session()->put("form_token_{$formToken}", [
        'csrf_token' => csrf_token(),
        'expires_at' => time() + 1800, // 30 minutes
    ]);
    
    return view('form.step1', ['formToken' => $formToken]);
}

public function submitForm(Request $request): Response
{
    $formToken = $request->input('form_token');
    $tokenData = session()->get("form_token_{$formToken}");
    
    if (!$tokenData || $tokenData['expires_at'] < time()) {
        abort(419, 'Form expired');
    }
    
    // Verify CSRF token
    if ($tokenData['csrf_token'] !== $request->input('_token')) {
        abort(419, 'CSRF token mismatch');
    }
    
    // Process form
    session()->forget("form_token_{$formToken}");
    
    return redirect('/success');
}
```

## Testing

### Disable in Tests

```php
// tests/TestCase.php
class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable CSRF for testing
        $this->withoutMiddleware(CsrfMiddleware::class);
    }
}
```

### Test with CSRF

```php
public function test_create_post_requires_csrf()
{
    $response = $this->post('/posts', [
        'title' => 'Test Post',
    ]);
    
    $response->assertStatus(419); // CSRF token mismatch
}

public function test_create_post_with_csrf()
{
    $response = $this->withCsrf()->post('/posts', [
        'title' => 'Test Post',
    ]);
    
    $response->assertStatus(201);
}
```

## Best Practices

1. **Always Use CSRF** - For all state-changing requests
2. **Exclude Carefully** - Only exclude known safe routes
3. **Use HTTPS** - Required for secure cookies
4. **SameSite Cookies** - Use 'lax' or 'strict'
5. **Token Rotation** - Regenerate after login/logout
6. **Short Lifetime** - Keep token TTL reasonable
7. **Validate Origin** - Check Origin/Referer headers
8. **Log Failures** - Track CSRF validation failures
9. **User Education** - Warn about suspicious requests
10. **Defense in Depth** - Combine with other security measures

## See Also

- [Authentication](authentication.md)
- [Security Best Practices](best-practices.md)
- [Sessions](../advanced/sessions.md)
