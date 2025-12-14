# Security Best Practices

Comprehensive security guidelines for NeoPhp Framework applications.

## Authentication & Authorization

### Secure Authentication

```php
// ✅ DO: Use prepared statements
$user = User::where('email', $email)->first();

// ❌ DON'T: Use raw queries with user input
$user = DB::raw("SELECT * FROM users WHERE email = '$email'");

// ✅ DO: Hash passwords with bcrypt/Argon2
$user->password = Hash::make($password);

// ❌ DON'T: Store plain text passwords
$user->password = $password;

// ✅ DO: Use CSRF protection
@csrf

// ❌ DON'T: Disable CSRF without good reason
$this->withoutMiddleware(CsrfMiddleware::class);
```

### Authorization Checks

```php
// ✅ DO: Check authorization before actions
$this->authorize('update', $post);
$post->update($request->validated());

// ❌ DON'T: Skip authorization checks
$post->update($request->all());

// ✅ DO: Use policies for resource authorization
Gate::define('update-post', [PostPolicy::class, 'update']);

// ❌ DON'T: Put authorization logic in controllers
if ($user->id === $post->user_id) { ... }
```

## Input Validation

### Validate All Input

```php
// ✅ DO: Validate all user input
$validated = $request->validate([
    'email' => 'required|email|max:255',
    'password' => 'required|min:8',
]);

// ❌ DON'T: Trust user input
$user->update($request->all());

// ✅ DO: Sanitize HTML input
$clean = strip_tags($request->input('content'));

// ❌ DON'T: Output raw HTML
echo $request->input('content');

// ✅ DO: Use validated data
User::create($validated);

// ❌ DON'T: Use unvalidated data
User::create($request->all());
```

### Mass Assignment Protection

```php
// ✅ DO: Define fillable fields
class User extends Model
{
    protected $fillable = ['name', 'email'];
}

// ❌ DON'T: Use guarded = []
protected $guarded = [];

// ✅ DO: Use validated data
$user->fill($validated);

// ❌ DON'T: Fill with all request data
$user->fill($request->all());
```

## SQL Injection Prevention

### Use Query Builder

```php
// ✅ DO: Use query builder with bindings
User::where('email', $email)->first();

// ❌ DON'T: Use raw SQL with concatenation
DB::raw("SELECT * FROM users WHERE email = '$email'");

// ✅ DO: Use bindings for raw queries
DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// ❌ DON'T: Concatenate user input
DB::select("SELECT * FROM users WHERE email = '$email'");
```

## XSS Prevention

### Escape Output

```php
// ✅ DO: Escape output in Blade
{{ $user->name }}

// ❌ DON'T: Output raw HTML
{!! $user->name !!}

// ✅ DO: Sanitize HTML when needed
{!! Purifier::clean($content) !!}

// ❌ DON'T: Trust user HTML
{!! $user->input !!}
```

### Content Security Policy

```php
// config/security.php
return [
    'csp' => [
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'unsafe-inline'"],
        'style-src' => ["'self'", "'unsafe-inline'"],
        'img-src' => ["'self'", 'data:', 'https:'],
    ],
];
```

## CSRF Protection

### Enable CSRF

```php
// ✅ DO: Use CSRF tokens in forms
<form method="POST">
    @csrf
    <!-- form fields -->
</form>

// ✅ DO: Send CSRF token in AJAX
axios.defaults.headers.common['X-CSRF-TOKEN'] = token;

// ❌ DON'T: Disable CSRF without reason
$except = ['/api/*']; // Be specific!
```

## Session Security

### Secure Session Configuration

```php
// config/session.php
return [
    'secure' => true,        // HTTPS only
    'http_only' => true,     // No JavaScript access
    'same_site' => 'lax',    // CSRF protection
    'lifetime' => 120,       // 2 hours
];

// ✅ DO: Regenerate session ID after login
session()->regenerate();

// ✅ DO: Invalidate session on logout
session()->invalidate();
session()->regenerateToken();
```

## Password Security

### Strong Password Policy

```php
// ✅ DO: Enforce strong passwords
Password::min(8)
    ->mixedCase()
    ->numbers()
    ->symbols()
    ->uncompromised();

// ✅ DO: Check against breached passwords
if (PasswordValidator::isCompromised($password)) {
    return back()->withErrors(['password' => 'Compromised password']);
}

// ✅ DO: Hash with bcrypt/Argon2
Hash::make($password);

// ❌ DON'T: Use weak hashing
md5($password);
sha1($password);
```

## API Security

### Rate Limiting

```php
// ✅ DO: Apply rate limiting
$router->middleware(['throttle:60,1'])->group(function($router) {
    $router->get('/api/posts', [PostController::class, 'index']);
});

// ✅ DO: Different limits for different endpoints
$router->post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');
```

### API Authentication

```php
// ✅ DO: Use JWT or OAuth2
$router->middleware(['auth:jwt'])->group(function($router) {
    // Protected routes
});

// ❌ DON'T: Use Basic Auth over HTTP
// Only use Basic Auth over HTTPS

// ✅ DO: Validate API keys
$apiKey = ApiKey::where('key', hash('sha256', $key))->first();
```

## File Upload Security

### Validate Uploads

```php
// ✅ DO: Validate file type and size
$request->validate([
    'file' => 'required|file|mimes:jpg,png,pdf|max:10240',
]);

// ✅ DO: Generate random filenames
$filename = Str::random(40) . '.' . $file->extension();

// ❌ DON'T: Use original filename
$filename = $file->getClientOriginalName();

// ✅ DO: Store outside public directory
Storage::disk('private')->put($filename, $file);

// ❌ DON'T: Store in public without validation
$file->move(public_path('uploads'), $filename);
```

### Scan Uploaded Files

```php
// ✅ DO: Scan for malware
if (!AntiVirus::scan($file)) {
    throw new \Exception('File contains malware');
}

// ✅ DO: Validate image dimensions
$image = Image::make($file);
if ($image->width() > 4000 || $image->height() > 4000) {
    throw new \Exception('Image too large');
}
```

## Database Security

### Use Transactions

```php
// ✅ DO: Use transactions for multiple operations
DB::transaction(function () use ($data) {
    $user = User::create($data['user']);
    $profile = Profile::create($data['profile']);
    $user->profile()->associate($profile);
});

// ❌ DON'T: Skip transactions for critical operations
User::create($data);
Profile::create($data);
```

### Encrypt Sensitive Data

```php
// ✅ DO: Encrypt sensitive database fields
class User extends Model
{
    protected $casts = [
        'ssn' => 'encrypted',
        'credit_card' => 'encrypted',
    ];
}

// ✅ DO: Use encryption for sensitive config
env('DB_PASSWORD') // Encrypted in production
```

## HTTPS & TLS

### Force HTTPS

```php
// ✅ DO: Force HTTPS in production
if (app()->environment('production')) {
    URL::forceScheme('https');
}

// ✅ DO: Use HSTS header
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// ✅ DO: Validate SSL certificates
'verify' => true, // Don't disable SSL verification
```

## Error Handling

### Secure Error Messages

```php
// ✅ DO: Show generic errors in production
if (app()->environment('production')) {
    return response()->json(['error' => 'An error occurred'], 500);
}

// ❌ DON'T: Expose stack traces in production
return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTrace()]);

// ✅ DO: Log detailed errors
Log::error('Payment failed', [
    'user_id' => $user->id,
    'amount' => $amount,
    'error' => $e->getMessage(),
]);
```

## Dependency Management

### Keep Dependencies Updated

```bash
# ✅ DO: Regularly update dependencies
composer update

# ✅ DO: Check for vulnerabilities
composer audit

# ✅ DO: Use specific versions
"require": {
    "vendor/package": "^2.0"
}

# ❌ DON'T: Use dev-master
"require": {
    "vendor/package": "dev-master"
}
```

## Environment Security

### Secure Configuration

```php
// .env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:... // Generate with: php artisan key:generate

// ✅ DO: Keep .env out of version control
// .gitignore
.env
.env.local
.env.production

// ❌ DON'T: Commit secrets
// ❌ DON'T: Use default keys in production
```

## Logging & Monitoring

### Security Logging

```php
// ✅ DO: Log security events
Log::channel('security')->warning('Failed login attempt', [
    'ip' => $request->ip(),
    'email' => $request->email,
]);

// ✅ DO: Monitor for suspicious activity
if ($failedAttempts > 10) {
    Log::alert('Possible brute force attack', ['ip' => $ip]);
    // Block IP or alert admin
}

// ❌ DON'T: Log sensitive data
Log::info('User login', ['password' => $password]); // ❌ Never log passwords
```

## Security Headers

### Add Security Headers

```php
// app/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle($request, $next)
    {
        $response = $next($request);
        
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=()');
        
        return $response;
    }
}
```

## Regular Security Audits

### Security Checklist

- [ ] All user input validated
- [ ] CSRF protection enabled
- [ ] SQL injection prevention (parameterized queries)
- [ ] XSS prevention (escaped output)
- [ ] Passwords hashed with bcrypt/Argon2
- [ ] HTTPS enforced
- [ ] Security headers configured
- [ ] Rate limiting implemented
- [ ] File upload validation
- [ ] Dependencies updated
- [ ] Error messages sanitized
- [ ] Logging enabled
- [ ] Access control implemented
- [ ] Session security configured
- [ ] API authentication secured

## See Also

- [Authentication](authentication.md)
- [Authorization](authorization.md)
- [CSRF Protection](csrf.md)
- [Rate Limiting](rate-limiting.md)
- [Password Management](passwords.md)
