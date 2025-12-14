# Authentication

Complete authentication system with multiple strategies.

## Quick Start

### Basic Authentication Setup

```php
// config/auth.php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'token',
            'provider' => 'users',
        ],
        'jwt' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'cycle',
            'model' => App\Models\User::class,
        ],
    ],
];
```

## Session Authentication

### Login

```php
use NeoPhp\Auth\Auth;

public function login(Request $request): Response
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect('/dashboard');
    }

    return back()->withErrors([
        'email' => 'Invalid credentials',
    ]);
}
```

### Logout

```php
public function logout(Request $request): Response
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    return redirect('/');
}
```

### Check Authentication

```php
// Check if authenticated
if (Auth::check()) {
    // User is logged in
}

// Check if guest
if (Auth::guest()) {
    // User is not logged in
}

// Get current user
$user = Auth::user();
$userId = Auth::id();
```

### Protect Routes

```php
// Using middleware
$router->middleware(['auth'])->group(function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/profile', [ProfileController::class, 'show']);
});
```

## JWT Authentication

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
        'user_id' => $user->id,
        'email' => $user->email,
        'exp' => time() + (60 * 60 * 24 * 7) // 7 days
    ]);

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
}
```

### Verify Token

```php
public function me(Request $request): Response
{
    try {
        $token = $request->bearerToken();
        $payload = JWT::decode($token);
        
        $user = User::find($payload['user_id']);
        
        return response()->json($user);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Invalid token'], 401);
    }
}
```

### Refresh Token

```php
public function refresh(Request $request): Response
{
    $token = $request->bearerToken();
    $payload = JWT::decode($token);
    
    $newToken = JWT::encode([
        'user_id' => $payload['user_id'],
        'email' => $payload['email'],
        'exp' => time() + (60 * 60 * 24 * 7)
    ]);
    
    return response()->json(['token' => $newToken]);
}
```

### Protect API Routes

```php
$router->middleware(['auth:jwt'])->prefix('/api')->group(function($router) {
    $router->get('/user', [ApiController::class, 'user']);
    $router->get('/posts', [PostController::class, 'index']);
});
```

## API Token Authentication

### Generate API Token

```php
use NeoPhp\Auth\TokenGenerator;

public function createToken(Request $request): Response
{
    $user = Auth::user();
    
    $token = TokenGenerator::generate();
    
    $user->api_tokens()->create([
        'name' => $request->input('name', 'default'),
        'token' => hash('sha256', $token),
        'abilities' => $request->input('abilities', ['*']),
    ]);
    
    return response()->json([
        'token' => $token,
    ]);
}
```

### Authenticate with Token

```php
// Middleware checks Authorization header
// Authorization: Bearer your-api-token-here

$router->middleware(['auth:api'])->group(function($router) {
    $router->get('/api/data', [ApiController::class, 'data']);
});
```

## Password Reset

### Request Reset

```php
use NeoPhp\Auth\PasswordReset;

public function forgotPassword(Request $request): Response
{
    $request->validate(['email' => 'required|email']);
    
    $user = User::where('email', $request->email)->first();
    
    if ($user) {
        $token = PasswordReset::createToken($user);
        
        // Send email
        Mail::to($user)->send(new ResetPasswordMail($token));
    }
    
    return response()->json([
        'message' => 'Reset link sent to your email'
    ]);
}
```

### Reset Password

```php
public function resetPassword(Request $request): Response
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);
    
    if (!PasswordReset::verify($request->token, $request->email)) {
        return response()->json(['error' => 'Invalid token'], 400);
    }
    
    $user = User::where('email', $request->email)->first();
    $user->password = password_hash($request->password, PASSWORD_BCRYPT);
    $user->save();
    
    PasswordReset::delete($request->token);
    
    return response()->json(['message' => 'Password reset successful']);
}
```

## Email Verification

### Send Verification Email

```php
use NeoPhp\Auth\EmailVerification;

public function sendVerification(User $user): void
{
    $token = EmailVerification::createToken($user);
    
    Mail::to($user)->send(new VerifyEmailMail($token));
}
```

### Verify Email

```php
public function verifyEmail(Request $request): Response
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
    ]);
    
    if (!EmailVerification::verify($request->token, $request->email)) {
        return response()->json(['error' => 'Invalid token'], 400);
    }
    
    $user = User::where('email', $request->email)->first();
    $user->email_verified_at = now();
    $user->save();
    
    EmailVerification::delete($request->token);
    
    return response()->json(['message' => 'Email verified']);
}
```

### Require Email Verification

```php
$router->middleware(['auth', 'verified'])->group(function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
});
```

## Two-Factor Authentication (2FA)

### Enable 2FA

```php
use NeoPhp\Auth\TwoFactor;

public function enable2FA(Request $request): Response
{
    $user = Auth::user();
    
    $secret = TwoFactor::generateSecret();
    $qrCode = TwoFactor::getQRCodeUrl($user->email, $secret);
    
    $user->two_factor_secret = encrypt($secret);
    $user->save();
    
    return response()->json([
        'secret' => $secret,
        'qr_code' => $qrCode,
    ]);
}
```

### Verify 2FA Code

```php
public function verify2FA(Request $request): Response
{
    $request->validate(['code' => 'required|digits:6']);
    
    $user = Auth::user();
    $secret = decrypt($user->two_factor_secret);
    
    if (!TwoFactor::verify($secret, $request->code)) {
        return response()->json(['error' => 'Invalid code'], 401);
    }
    
    $user->two_factor_enabled = true;
    $user->save();
    
    return response()->json(['message' => '2FA enabled']);
}
```

### Login with 2FA

```php
public function login2FA(Request $request): Response
{
    // First verify password
    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        
        if ($user->two_factor_enabled) {
            // Store in session and require 2FA code
            $request->session()->put('2fa_required', $user->id);
            return response()->json(['require_2fa' => true]);
        }
        
        return response()->json(['token' => $token]);
    }
    
    return response()->json(['error' => 'Invalid credentials'], 401);
}

public function verify2FALogin(Request $request): Response
{
    $userId = $request->session()->get('2fa_required');
    $user = User::find($userId);
    
    $secret = decrypt($user->two_factor_secret);
    
    if (!TwoFactor::verify($secret, $request->code)) {
        return response()->json(['error' => 'Invalid 2FA code'], 401);
    }
    
    Auth::login($user);
    $request->session()->forget('2fa_required');
    
    return response()->json(['message' => 'Login successful']);
}
```

## Remember Me

```php
public function login(Request $request): Response
{
    $remember = $request->input('remember', false);
    
    if (Auth::attempt($credentials, $remember)) {
        return redirect('/dashboard');
    }
    
    return back()->withErrors(['email' => 'Invalid credentials']);
}
```

## User Impersonation

```php
public function impersonate(int $userId): Response
{
    if (!Auth::user()->isAdmin()) {
        abort(403);
    }
    
    $user = User::findOrFail($userId);
    
    session(['impersonate' => Auth::id()]);
    Auth::login($user);
    
    return redirect('/dashboard');
}

public function stopImpersonating(): Response
{
    $originalUserId = session('impersonate');
    
    if ($originalUserId) {
        $user = User::find($originalUserId);
        Auth::login($user);
        session()->forget('impersonate');
    }
    
    return redirect('/admin/users');
}
```

## Social Authentication

### OAuth Login

```php
public function redirectToProvider(string $provider): Response
{
    return Socialite::driver($provider)->redirect();
}

public function handleProviderCallback(string $provider): Response
{
    $socialUser = Socialite::driver($provider)->user();
    
    $user = User::firstOrCreate([
        'email' => $socialUser->getEmail(),
    ], [
        'name' => $socialUser->getName(),
        'avatar' => $socialUser->getAvatar(),
    ]);
    
    Auth::login($user);
    
    return redirect('/dashboard');
}
```

## Best Practices

1. **Use HTTPS** - Always use SSL in production
2. **Hash Passwords** - Use bcrypt or Argon2
3. **Validate Input** - Always validate credentials
4. **Regenerate Sessions** - After login to prevent fixation
5. **Use CSRF Protection** - For form submissions
6. **Implement Rate Limiting** - Prevent brute force attacks
7. **Log Authentication Events** - Track login attempts
8. **Use Secure Cookies** - Set httpOnly and secure flags
9. **Implement 2FA** - For sensitive accounts
10. **Regular Security Audits** - Keep system updated

## See Also

- [Authorization](authorization.md)
- [RBAC](rbac.md)
- [JWT Tokens](jwt.md)
- [Security Best Practices](best-practices.md)
