# Password Management

Secure password hashing, validation, and reset functionality.

## Password Hashing

### Hash Passwords

```php
use NeoPhp\Security\Hash;

// Hash with bcrypt (default)
$hashedPassword = Hash::make('secret-password');

// Hash with Argon2
$hashedPassword = Hash::make('secret-password', ['algorithm' => 'argon2']);

// Hash with custom cost
$hashedPassword = Hash::make('secret-password', [
    'algorithm' => 'bcrypt',
    'cost' => 12,
]);
```

### Verify Passwords

```php
$isValid = Hash::check('secret-password', $hashedPassword);

if ($isValid) {
    // Password is correct
}
```

### Rehash if Needed

```php
if (Hash::needsRehash($hashedPassword)) {
    $user->password = Hash::make($plainPassword);
    $user->save();
}
```

## Password Validation

### Validation Rules

```php
$request->validate([
    'password' => [
        'required',
        'string',
        'min:8',
        'max:255',
        'regex:/[a-z]/',      // Must contain lowercase
        'regex:/[A-Z]/',      // Must contain uppercase
        'regex:/[0-9]/',      // Must contain number
        'regex:/[@$!%*#?&]/', // Must contain special char
        'confirmed',          // Must match password_confirmation
    ],
]);
```

### Custom Password Rules

```php
use NeoPhp\Validation\Rules\Password;

$request->validate([
    'password' => [
        'required',
        Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(),
    ],
]);
```

### Check Compromised Passwords

```php
use NeoPhp\Security\PasswordValidator;

$password = 'password123';

if (PasswordValidator::isCompromised($password)) {
    return back()->withErrors([
        'password' => 'This password has been compromised in a data breach.'
    ]);
}
```

## Password Reset

### Request Reset Link

```php
use NeoPhp\Auth\PasswordReset;
use NeoPhp\Mail\Mail;

public function forgotPassword(Request $request): Response
{
    $request->validate(['email' => 'required|email']);
    
    $user = User::where('email', $request->email)->first();
    
    if (!$user) {
        return back()->with('status', 'Reset link sent if email exists.');
    }
    
    // Generate token
    $token = PasswordReset::createToken($user);
    
    // Send email
    Mail::to($user)->send(new ResetPasswordMail($user, $token));
    
    return back()->with('status', 'Reset link sent to your email.');
}
```

### Reset Password

```php
public function resetPassword(Request $request): Response
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => [
            'required',
            'confirmed',
            Password::min(8)->mixedCase()->numbers(),
        ],
    ]);
    
    // Verify token
    if (!PasswordReset::verify($request->token, $request->email)) {
        return back()->withErrors(['email' => 'Invalid or expired reset link.']);
    }
    
    // Update password
    $user = User::where('email', $request->email)->first();
    $user->password = Hash::make($request->password);
    $user->password_changed_at = now();
    $user->save();
    
    // Delete reset token
    PasswordReset::delete($request->token);
    
    // Send confirmation email
    Mail::to($user)->send(new PasswordChangedMail($user));
    
    return redirect('/login')->with('status', 'Password reset successfully.');
}
```

### Token Storage

```php
// database/migrations/create_password_resets_table.php
Schema::create('password_resets', function ($table) {
    $table->string('email')->index();
    $table->string('token');
    $table->timestamp('created_at');
    
    $table->index(['email', 'token']);
});
```

### Password Reset Implementation

```php
namespace NeoPhp\Auth;

use Illuminate\Support\Str;

class PasswordReset
{
    public static function createToken(User $user): string
    {
        $token = Str::random(64);
        
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => hash('sha256', $token),
            'created_at' => now(),
        ]);
        
        return $token;
    }
    
    public static function verify(string $token, string $email): bool
    {
        $reset = DB::table('password_resets')
            ->where('email', $email)
            ->where('token', hash('sha256', $token))
            ->where('created_at', '>', now()->subHours(1))
            ->first();
            
        return $reset !== null;
    }
    
    public static function delete(string $token): void
    {
        DB::table('password_resets')
            ->where('token', hash('sha256', $token))
            ->delete();
    }
    
    public static function cleanup(): void
    {
        DB::table('password_resets')
            ->where('created_at', '<', now()->subHours(1))
            ->delete();
    }
}
```

## Password Change

### Change Password

```php
public function changePassword(Request $request): Response
{
    $request->validate([
        'current_password' => 'required',
        'password' => [
            'required',
            'confirmed',
            'different:current_password',
            Password::min(8)->mixedCase()->numbers(),
        ],
    ]);
    
    $user = Auth::user();
    
    // Verify current password
    if (!Hash::check($request->current_password, $user->password)) {
        return back()->withErrors(['current_password' => 'Incorrect password.']);
    }
    
    // Update password
    $user->password = Hash::make($request->password);
    $user->password_changed_at = now();
    $user->save();
    
    // Invalidate other sessions
    Auth::logoutOtherDevices($request->password);
    
    // Send notification
    Mail::to($user)->send(new PasswordChangedMail($user));
    
    return back()->with('status', 'Password changed successfully.');
}
```

## Password Expiry

### Force Password Change

```php
// app/Middleware/CheckPasswordExpiry.php
class CheckPasswordExpiry
{
    public function handle($request, $next)
    {
        $user = Auth::user();
        
        if (!$user->password_changed_at) {
            return redirect('/password/change')
                ->with('warning', 'Please set a new password.');
        }
        
        $expiryDays = config('auth.password_expiry_days', 90);
        $passwordAge = now()->diffInDays($user->password_changed_at);
        
        if ($passwordAge > $expiryDays) {
            return redirect('/password/change')
                ->with('warning', 'Your password has expired. Please change it.');
        }
        
        return $next($request);
    }
}
```

## Password History

### Prevent Reuse

```php
// database/migrations/create_password_history_table.php
Schema::create('password_history', function ($table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('password');
    $table->timestamp('created_at');
});

// Check password history
public function changePassword(Request $request): Response
{
    $user = Auth::user();
    
    // Get last 5 passwords
    $previousPasswords = DB::table('password_history')
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->pluck('password');
    
    // Check if new password matches any previous
    foreach ($previousPasswords as $oldPassword) {
        if (Hash::check($request->password, $oldPassword)) {
            return back()->withErrors([
                'password' => 'Cannot reuse previous passwords.'
            ]);
        }
    }
    
    // Save new password
    $newPassword = Hash::make($request->password);
    $user->password = $newPassword;
    $user->save();
    
    // Store in history
    DB::table('password_history')->insert([
        'user_id' => $user->id,
        'password' => $newPassword,
        'created_at' => now(),
    ]);
    
    return back()->with('status', 'Password changed successfully.');
}
```

## Temporary Passwords

### Generate Temporary Password

```php
use Illuminate\Support\Str;

public function generateTemporaryPassword(User $user): string
{
    $password = Str::random(12);
    
    $user->password = Hash::make($password);
    $user->must_change_password = true;
    $user->save();
    
    Mail::to($user)->send(new TemporaryPasswordMail($user, $password));
    
    return $password;
}
```

### Force Password Change

```php
// app/Middleware/MustChangePassword.php
class MustChangePassword
{
    public function handle($request, $next)
    {
        if (Auth::user()->must_change_password) {
            if ($request->route()->getName() !== 'password.change') {
                return redirect()->route('password.change')
                    ->with('warning', 'You must change your temporary password.');
            }
        }
        
        return $next($request);
    }
}
```

## Multi-Factor Authentication

### Require Password for Sensitive Actions

```php
public function enableTwoFactor(Request $request): Response
{
    // Require password confirmation
    $request->validate([
        'password' => 'required|current_password',
    ]);
    
    // Enable 2FA
    $user = Auth::user();
    $user->enableTwoFactorAuth();
    
    return response()->json(['message' => '2FA enabled']);
}
```

## Password Strength Meter

### JavaScript Implementation

```javascript
function checkPasswordStrength(password) {
    let strength = 0;
    
    // Length
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Character types
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    // Patterns
    if (!/(.)\1{2,}/.test(password)) strength++; // No repeating chars
    if (!/(?:abc|bcd|cde|def|012|123|234)/.test(password)) strength++; // No sequences
    
    return {
        score: strength,
        label: strength < 3 ? 'Weak' : strength < 5 ? 'Medium' : strength < 7 ? 'Strong' : 'Very Strong'
    };
}
```

## Best Practices

1. **Use Strong Hashing** - bcrypt or Argon2
2. **Minimum Length** - At least 8 characters
3. **Complexity Requirements** - Mix of character types
4. **Check Breaches** - Verify against known compromised passwords
5. **Password History** - Prevent reuse of old passwords
6. **Password Expiry** - Force periodic changes for sensitive systems
7. **Secure Reset** - Use time-limited tokens
8. **Rate Limiting** - Limit reset requests
9. **Notification** - Email users on password changes
10. **Never Store Plain** - Always hash passwords

## See Also

- [Authentication](authentication.md)
- [Security Best Practices](best-practices.md)
- [Rate Limiting](rate-limiting.md)
