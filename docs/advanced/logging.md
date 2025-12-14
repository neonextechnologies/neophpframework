# Logging

Record application events, errors, and debugging information.

## Configuration

### Setup Logger

```php
// config/logging.php
return [
    'default' => env('LOG_CHANNEL', 'stack'),
    
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'daily'],
        ],
        
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/app.log'),
            'level' => 'debug',
        ],
        
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/app.log'),
            'level' => 'debug',
            'days' => 14,
        ],
        
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],
    ],
];
```

## Basic Logging

### Log Messages

```php
use NeoPhp\Support\Facades\Log;

// Debug
Log::debug('Debug message', ['context' => 'value']);

// Info
Log::info('User logged in', ['user_id' => 1]);

// Notice
Log::notice('Unusual activity detected');

// Warning
Log::warning('Low disk space', ['available' => '10GB']);

// Error
Log::error('Payment failed', [
    'order_id' => 123,
    'error' => $exception->getMessage(),
]);

// Critical
Log::critical('Database connection lost');

// Alert
Log::alert('Website down');

// Emergency
Log::emergency('System failure');
```

## Using Logger Service

### Dependency Injection

```php
use Psr\Log\LoggerInterface;

class PaymentController
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    public function process(Request $request)
    {
        $this->logger->info('Processing payment', [
            'amount' => $request->input('amount'),
            'user_id' => auth()->id(),
        ]);
        
        try {
            // Process payment
            $this->logger->info('Payment successful');
        } catch (Exception $e) {
            $this->logger->error('Payment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
```

## Contextual Logging

### Add Context

```php
// With context array
Log::info('User action', [
    'user_id' => 1,
    'action' => 'login',
    'ip' => request()->ip(),
    'timestamp' => now(),
]);

// With exception
try {
    // Code
} catch (Exception $e) {
    Log::error('Operation failed', [
        'exception' => $e,
        'user_id' => auth()->id(),
    ]);
}
```

## Custom Channels

### Log to Specific Channel

```php
// Use specific channel
Log::channel('slack')->critical('Production error');

// Stack multiple channels
Log::stack(['single', 'slack'])->info('Important event');

// Custom channel
Log::build([
    'driver' => 'single',
    'path' => storage_path('logs/custom.log'),
])->info('Custom log');
```

## Monolog Customization

### Configure Monolog

```php
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;

$logger = new Logger('app');

$handler = new StreamHandler(storage_path('logs/app.log'), Logger::DEBUG);

$formatter = new LineFormatter(
    "[%datetime%] %channel%.%level_name%: %message% %context%\n",
    'Y-m-d H:i:s'
);

$handler->setFormatter($formatter);
$logger->pushHandler($handler);
```

## Log Formatters

### Custom Format

```php
// config/logging.php
'custom' => [
    'driver' => 'single',
    'path' => storage_path('logs/custom.log'),
    'level' => 'debug',
    'formatter' => \App\Logging\CustomFormatter::class,
],

// App/Logging/CustomFormatter.php
namespace App\Logging;

use Monolog\Formatter\LineFormatter;

class CustomFormatter extends LineFormatter
{
    public function __construct()
    {
        parent::__construct(
            "[%datetime%] [%level_name%] %message% %context%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
    }
}
```

## Log Rotation

### Daily Logs

```php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/app.log'),
    'level' => 'debug',
    'days' => 14, // Keep 14 days
],
```

### Size-based Rotation

```php
use Monolog\Handler\RotatingFileHandler;

$logger->pushHandler(
    new RotatingFileHandler(
        storage_path('logs/app.log'),
        30, // Max files
        Logger::DEBUG
    )
);
```

## Filtering Logs

### By Level

```php
// Only log errors and above
'production' => [
    'driver' => 'daily',
    'path' => storage_path('logs/production.log'),
    'level' => 'error',
],
```

### By Environment

```php
if (app()->environment('production')) {
    Log::channel('slack')->error('Production error', $context);
} else {
    Log::channel('single')->error('Development error', $context);
}
```

## Performance Logging

### Measure Execution Time

```php
$start = microtime(true);

// Code to measure

$duration = microtime(true) - $start;

Log::info('Operation completed', [
    'duration' => $duration,
    'memory' => memory_get_peak_usage(true),
]);
```

### Query Logging

```php
DB::listen(function ($query) {
    Log::debug('Query executed', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
    ]);
});
```

## Security Logging

### Audit Trail

```php
Log::channel('audit')->info('User action', [
    'user_id' => auth()->id(),
    'action' => 'update_profile',
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'changes' => $changes,
]);
```

### Authentication Logs

```php
// Successful login
Log::info('User logged in', [
    'user_id' => $user->id,
    'ip' => request()->ip(),
]);

// Failed login
Log::warning('Failed login attempt', [
    'email' => request()->input('email'),
    'ip' => request()->ip(),
]);
```

## Error Logging

### Exception Handler

```php
namespace App\Exceptions;

use Exception;
use Psr\Log\LoggerInterface;

class Handler
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    public function report(Exception $exception)
    {
        if ($this->shouldReport($exception)) {
            $this->logger->error($exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }
}
```

## Remote Logging

### Slack Notifications

```php
'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'username' => 'App Logger',
    'emoji' => ':boom:',
    'level' => 'error',
],

// Usage
Log::channel('slack')->error('Critical error occurred');
```

### Syslog

```php
'syslog' => [
    'driver' => 'syslog',
    'level' => 'debug',
],
```

### Papertrail

```php
'papertrail' => [
    'driver' => 'monolog',
    'handler' => \Monolog\Handler\SyslogUdpHandler::class,
    'handler_with' => [
        'host' => env('PAPERTRAIL_URL'),
        'port' => env('PAPERTRAIL_PORT'),
    ],
],
```

## Testing Logs

### Assert Logs

```php
Log::shouldReceive('info')
    ->once()
    ->with('User logged in', ['user_id' => 1]);
```

## Best Practices

1. **Appropriate Levels** - Use correct log levels
2. **Context** - Include relevant context data
3. **Security** - Don't log sensitive data (passwords, tokens)
4. **Performance** - Be mindful of logging overhead
5. **Rotation** - Implement log rotation
6. **Monitoring** - Set up alerts for critical errors
7. **Structure** - Use consistent log format

## See Also

- [Error Tracking](error-tracking.md)
- [Monitoring](../basics/monitoring.md)
- [Security Best Practices](../security/best-practices.md)
