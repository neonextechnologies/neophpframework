# Queue System

Process time-consuming tasks in the background.

## Configuration

### Queue Setup

```php
// config/queue.php
return [
    'default' => env('QUEUE_CONNECTION', 'redis'),
    
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],
        
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 5,
        ],
    ],
    
    'failed' => [
        'driver' => 'database',
        'table' => 'failed_jobs',
    ],
];
```

## Creating Jobs

### Job Class

```php
namespace App\Jobs;

use NeoPhp\Queue\Job;

class SendEmail extends Job
{
    public function __construct(
        public User $user,
        public string $message
    ) {}
    
    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new GenericEmail($this->message)
        );
    }
}
```

## Dispatching Jobs

### Queue Jobs

```php
use App\Jobs\SendEmail;

// Dispatch immediately
SendEmail::dispatch($user, 'Hello!');

// Dispatch after delay
SendEmail::dispatch($user, 'Hello!')
    ->delay(now()->addMinutes(10));

// Dispatch to specific queue
SendEmail::dispatch($user, 'Hello!')
    ->onQueue('emails');

// Dispatch to specific connection
SendEmail::dispatch($user, 'Hello!')
    ->onConnection('redis');
```

## Job Chains

### Sequential Jobs

```php
use NeoPhp\Queue\Chain;

Chain::create([
    new ProcessVideo($video),
    new OptimizeVideo($video),
    new PublishVideo($video),
])->dispatch();

// With delay
Chain::create([
    new Job1(),
    new Job2(),
])->delay(now()->addMinutes(5))->dispatch();
```

## Job Batches

### Batch Processing

```php
use NeoPhp\Queue\Batch;

$batch = Batch::create([
    new ImportRow($row1),
    new ImportRow($row2),
    new ImportRow($row3),
])->then(function () {
    // All jobs completed
})->catch(function () {
    // One or more jobs failed
})->finally(function () {
    // Batch completed
})->dispatch();

// Check batch status
if ($batch->finished()) {
    // All jobs complete
}
```

## Job Middleware

### Rate Limiting

```php
use NeoPhp\Queue\Middleware\RateLimited;

class SendEmail extends Job
{
    public function middleware(): array
    {
        return [
            new RateLimited('emails', 10, 60), // 10 per minute
        ];
    }
}
```

### Without Overlapping

```php
use NeoPhp\Queue\Middleware\WithoutOverlapping;

class ProcessReport extends Job
{
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->user->id),
        ];
    }
}
```

## Failed Jobs

### Handle Failures

```php
class SendEmail extends Job
{
    public int $tries = 3;
    public int $timeout = 30;
    public int $maxExceptions = 3;
    
    public function failed(\Throwable $exception): void
    {
        // Notify user of failure
        Log::error('Failed to send email', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Retry Failed Jobs

```bash
# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry 1

# Retry all failed jobs
php artisan queue:retry all

# Delete failed job
php artisan queue:forget 1

# Flush all failed jobs
php artisan queue:flush
```

## Queue Workers

### Run Worker

```bash
# Start queue worker
php artisan queue:work

# Specific connection
php artisan queue:work redis

# Specific queue
php artisan queue:work --queue=high,default

# Process single job
php artisan queue:work --once

# Daemon mode
php artisan queue:work --daemon

# With memory limit
php artisan queue:work --memory=128

# With timeout
php artisan queue:work --timeout=60

# Stop after processing jobs
php artisan queue:work --stop-when-empty
```

## Supervisor Configuration

### Setup Supervisor

```ini
[program:neophp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/worker.log
```

## Job Priority

### Priority Queues

```php
// Dispatch to high priority queue
SendEmail::dispatch($user, 'Urgent!')
    ->onQueue('high');

// Worker processes high priority first
php artisan queue:work --queue=high,default,low
```

## Job Events

### Listen to Job Events

```php
use NeoPhp\Queue\Events\JobProcessed;
use NeoPhp\Queue\Events\JobFailed;

Event::listen(JobProcessed::class, function ($event) {
    Log::info('Job processed', [
        'job' => $event->job->getName(),
    ]);
});

Event::listen(JobFailed::class, function ($event) {
    Log::error('Job failed', [
        'job' => $event->job->getName(),
        'exception' => $event->exception->getMessage(),
    ]);
});
```

## Testing Jobs

### Assert Jobs

```php
use NeoPhp\Testing\Facades\Queue;

public function test_job_is_dispatched()
{
    Queue::fake();
    
    // Perform action
    $this->post('/send-email', ['user_id' => 1]);
    
    // Assert job was dispatched
    Queue::assertPushed(SendEmail::class, function ($job) {
        return $job->user->id === 1;
    });
    
    // Assert job was not dispatched
    Queue::assertNotPushed(SendSms::class);
    
    // Assert job count
    Queue::assertPushed(SendEmail::class, 2);
}
```

## Best Practices

1. **Idempotent Jobs** - Jobs should be safely retryable
2. **Timeouts** - Set appropriate timeouts
3. **Error Handling** - Handle failures gracefully
4. **Monitoring** - Monitor queue length and failures
5. **Priority Queues** - Use for time-sensitive jobs
6. **Resource Limits** - Set memory and time limits
7. **Clean Up** - Clean up failed jobs regularly

## See Also

- [Events](events.md)
- [Scheduler](../SCHEDULER.md)
- [Broadcasting](broadcasting.md)
