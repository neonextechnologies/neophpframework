# Error Tracking

Monitor and track application errors and exceptions.

## Exception Handler

### Custom Exception Handler

```php
namespace App\Exceptions;

use Exception;
use Throwable;
use NeoPhp\Http\Request;
use NeoPhp\Http\Response;

class Handler
{
    protected $dontReport = [
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ];
    
    public function report(Throwable $exception)
    {
        if ($this->shouldReport($exception)) {
            // Log exception
            logger()->error($exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
            
            // Send to error tracking service
            if (app()->bound('sentry')) {
                app('sentry')->captureException($exception);
            }
        }
    }
    
    public function render(Request $request, Throwable $exception): Response
    {
        // API requests return JSON
        if ($request->expectsJson()) {
            return $this->renderJsonException($exception);
        }
        
        // Web requests return views
        return $this->renderWebException($exception);
    }
    
    protected function shouldReport(Throwable $exception): bool
    {
        foreach ($this->dontReport as $type) {
            if ($exception instanceof $type) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function renderJsonException(Throwable $exception): Response
    {
        $statusCode = $this->getStatusCode($exception);
        
        return response()->json([
            'error' => [
                'message' => $exception->getMessage(),
                'code' => $statusCode,
            ],
        ], $statusCode);
    }
    
    protected function renderWebException(Throwable $exception): Response
    {
        $statusCode = $this->getStatusCode($exception);
        
        if (view()->exists("errors.{$statusCode}")) {
            return response()->view("errors.{$statusCode}", [
                'exception' => $exception,
            ], $statusCode);
        }
        
        return response()->view('errors.default', [
            'exception' => $exception,
        ], $statusCode);
    }
    
    protected function getStatusCode(Throwable $exception): int
    {
        return method_exists($exception, 'getStatusCode')
            ? $exception->getStatusCode()
            : 500;
    }
}
```

## Custom Exceptions

### Create Custom Exception

```php
namespace App\Exceptions;

use Exception;

class PaymentFailedException extends Exception
{
    protected $code = 402;
    protected $message = 'Payment processing failed';
    
    public function __construct(string $message = '', array $context = [])
    {
        parent::__construct($message ?: $this->message);
        $this->context = $context;
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
    
    public function report()
    {
        // Custom reporting logic
        logger()->error($this->getMessage(), $this->context);
    }
    
    public function render($request)
    {
        return response()->json([
            'error' => 'Payment failed',
            'message' => $this->getMessage(),
        ], $this->code);
    }
}
```

### Use Custom Exception

```php
use App\Exceptions\PaymentFailedException;

if (!$payment->process()) {
    throw new PaymentFailedException('Payment gateway error', [
        'gateway' => 'stripe',
        'amount' => $payment->amount,
    ]);
}
```

## Sentry Integration

### Install Sentry

```bash
composer require sentry/sentry-laravel
```

### Configure Sentry

```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    
    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
    ],
    
    'environment' => env('APP_ENV', 'production'),
    
    'release' => env('APP_VERSION'),
];
```

### Send to Sentry

```php
use Sentry\Laravel\Facade as Sentry;

try {
    // Code
} catch (Exception $e) {
    Sentry::captureException($e);
    
    // With context
    Sentry::withScope(function ($scope) use ($e) {
        $scope->setUser([
            'id' => auth()->id(),
            'email' => auth()->user()->email,
        ]);
        
        $scope->setTag('payment_gateway', 'stripe');
        
        Sentry::captureException($e);
    });
}
```

## Error Pages

### Custom Error Views

```blade
{{-- resources/views/errors/404.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Page Not Found</title>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p>The page you're looking for doesn't exist.</p>
    <a href="/">Go Home</a>
</body>
</html>

{{-- resources/views/errors/500.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Server Error</title>
</head>
<body>
    <h1>500 - Server Error</h1>
    <p>Something went wrong on our end.</p>
    
    @if(config('app.debug'))
        <pre>{{ $exception->getMessage() }}</pre>
        <pre>{{ $exception->getTraceAsString() }}</pre>
    @endif
</body>
</html>
```

## Error Context

### Add Context to Errors

```php
try {
    // Risky operation
    $result = $api->call();
} catch (Exception $e) {
    logger()->error('API call failed', [
        'endpoint' => $api->endpoint,
        'method' => 'POST',
        'user_id' => auth()->id(),
        'request_id' => request()->id(),
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    throw $e;
}
```

## Error Monitoring

### Monitor Application Health

```php
namespace App\Services;

class HealthMonitor
{
    public function check(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];
    }
    
    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Exception $e) {
            logger()->critical('Database connection failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    protected function checkCache(): bool
    {
        try {
            cache()->put('health_check', true, 60);
            return cache()->get('health_check') === true;
        } catch (Exception $e) {
            logger()->critical('Cache check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    protected function checkStorage(): bool
    {
        try {
            $disk = Storage::disk();
            $disk->put('health_check.txt', 'OK');
            $result = $disk->get('health_check.txt') === 'OK';
            $disk->delete('health_check.txt');
            return $result;
        } catch (Exception $e) {
            logger()->critical('Storage check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    protected function checkQueue(): bool
    {
        try {
            // Check queue connection
            return Queue::connection()->size() >= 0;
        } catch (Exception $e) {
            logger()->critical('Queue check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
```

## Error Notifications

### Notify on Critical Errors

```php
namespace App\Exceptions;

use App\Notifications\CriticalErrorNotification;

class Handler
{
    public function report(Throwable $exception)
    {
        if ($this->isCritical($exception)) {
            // Notify admin
            $admins = User::where('role', 'admin')->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new CriticalErrorNotification($exception));
            }
            
            // Send to Slack
            Log::channel('slack')->critical('Critical error', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
        
        parent::report($exception);
    }
    
    protected function isCritical(Throwable $exception): bool
    {
        return $exception instanceof \PDOException
            || $exception instanceof \RuntimeException;
    }
}
```

## Error Rate Limiting

### Prevent Error Spam

```php
namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ErrorRateLimiter
{
    public function shouldReport(Throwable $exception): bool
    {
        $key = 'error_' . md5(
            get_class($exception) .
            $exception->getFile() .
            $exception->getLine()
        );
        
        if (Cache::has($key)) {
            return false;
        }
        
        // Report once per hour
        Cache::put($key, true, 3600);
        
        return true;
    }
}
```

## Best Practices

1. **Don't Report All** - Exclude expected exceptions
2. **Context** - Include relevant context data
3. **Security** - Don't expose sensitive data
4. **User Experience** - Show friendly error pages
5. **Monitoring** - Set up alerts for critical errors
6. **Rate Limiting** - Prevent error spam
7. **Testing** - Test error handling thoroughly

## See Also

- [Logging](logging.md)
- [Exceptions](https://www.php.net/manual/en/language.exceptions.php)
- [Security Best Practices](../security/best-practices.md)
