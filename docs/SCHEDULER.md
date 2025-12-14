# Task Scheduler

The NeoPhp Task Scheduler provides a fluent API for scheduling recurring tasks using cron expressions.

## Table of Contents

- [Configuration](#configuration)
- [Defining Schedules](#defining-schedules)
- [Schedule Frequencies](#schedule-frequencies)
- [Preventing Task Overlaps](#preventing-task-overlaps)
- [Running the Scheduler](#running-the-scheduler)
- [System Cron Setup](#system-cron-setup)
- [CLI Commands](#cli-commands)

## Configuration

Schedule configuration is stored in `config/schedule.php`:

```php
return [
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'prevent_overlap' => true,
    'lock_path' => __DIR__ . '/../storage/framework/schedule',
    
    'tasks' => [
        [
            'command' => 'logs:clear',
            'frequency' => '0 0 * * *', // Daily at midnight
            'description' => 'Clear old logs',
            'without_overlapping' => true,
        ],
    ],
];
```

## Defining Schedules

You can define scheduled tasks in several ways:

### Schedule Commands

```php
use NeoPhp\Schedule\Scheduler;

$scheduler = new Scheduler();

// Schedule a CLI command
$scheduler->command('logs:clear')->daily();

// With description
$scheduler->command('backup:database')
    ->daily()
    ->description('Backup database daily');
```

### Schedule Closures

```php
$scheduler->call(function() {
    // Your code here
    echo "Task executed!";
})->hourly();

// With parameters
$scheduler->call(function($name) {
    echo "Hello, {$name}!";
}, ['World'])->everyFiveMinutes();
```

### Schedule Jobs

```php
$scheduler->job(SendEmailJob::class, ['email' => 'user@example.com'])
    ->dailyAt('09:00');
```

### Raw Cron Expressions

```php
$scheduler->cron('*/5 * * * *', 'php artisan cache:clear')
    ->description('Clear cache every 5 minutes');
```

## Schedule Frequencies

The scheduler supports a variety of frequency methods:

### Time-Based

```php
// Every minute
$task->everyMinute();

// Every N minutes
$task->everyFiveMinutes();
$task->everyTenMinutes();
$task->everyFifteenMinutes();
$task->everyThirtyMinutes();

// Hourly
$task->hourly();
$task->hourlyAt(15); // At 15 minutes past the hour

// Daily
$task->daily();
$task->dailyAt('13:00'); // At 1:00 PM
$task->twiceDaily(1, 13); // At 1:00 AM and 1:00 PM

// Weekly
$task->weekly();
$task->weeklyOn(1, '8:00'); // Every Monday at 8:00 AM

// Monthly
$task->monthly();
$task->monthlyOn(15, '9:00'); // 15th of every month at 9:00 AM

// Quarterly & Yearly
$task->quarterly(); // First day of every quarter
$task->yearly(); // January 1st at midnight
```

### Day-Based

```php
// Weekdays vs Weekends
$task->weekdays(); // Monday through Friday
$task->weekends(); // Saturday and Sunday

// Specific days
$task->sundays();
$task->mondays();
$task->tuesdays();
$task->wednesdays();
$task->thursdays();
$task->fridays();
$task->saturdays();
```

### Custom Cron Expressions

```php
// Standard cron format: minute hour day month weekday
$task->cron('*/5 * * * *'); // Every 5 minutes
$task->cron('0 */2 * * *'); // Every 2 hours
$task->cron('0 0 * * 0');   // Every Sunday at midnight
$task->cron('0 9 1 * *');   // First day of month at 9 AM
```

### Cron Expression Special Characters

- `*` - Any value
- `,` - Value list separator (e.g., `1,3,5`)
- `-` - Range of values (e.g., `1-5`)
- `/` - Step values (e.g., `*/5` = every 5)
- `L` - Last (e.g., `L` = last day of month, `5L` = last Friday)
- `W` - Nearest weekday (e.g., `15W` = weekday nearest to 15th)
- `#` - Nth occurrence (e.g., `1#2` = second Monday)

## Preventing Task Overlaps

Prevent tasks from running if the previous execution is still running:

```php
$scheduler->command('backup:database')
    ->daily()
    ->withoutOverlapping(); // Lock expires after 24 hours

// Custom expiration (in minutes)
$scheduler->command('long-running-task')
    ->hourly()
    ->withoutOverlapping(120); // Lock expires after 2 hours
```

## Background Execution

Run tasks in the background without blocking:

```php
$scheduler->command('send:emails')
    ->daily()
    ->runInBackground();
```

## Conditional Execution

Execute tasks based on conditions:

```php
// Only run when condition is true
$scheduler->command('backup:database')
    ->daily()
    ->when(function() {
        return date('N') == 5; // Only on Fridays
    });

// Skip when condition is true
$scheduler->command('maintenance:mode')
    ->hourly()
    ->skip(function() {
        return isMaintenanceMode();
    });

// Only run in specific environments
$scheduler->command('test:command')
    ->daily()
    ->environments(['staging', 'development']);
```

## Running the Scheduler

### Via CLI

Run all due tasks manually:

```bash
php neo schedule:run
```

List all scheduled tasks:

```bash
php neo schedule:list
```

## System Cron Setup

To run scheduled tasks automatically, set up a system cron job.

### Linux/macOS

Edit your crontab:

```bash
crontab -e
```

Add this line to run the scheduler every minute:

```cron
* * * * * cd /path/to/your/project && php neo schedule:run >> /dev/null 2>&1
```

Or with logging:

```cron
* * * * * cd /path/to/your/project && php neo schedule:run >> /path/to/scheduler.log 2>&1
```

### Windows Task Scheduler

1. Open Task Scheduler (`taskschd.msc`)
2. Create a new task:
   - **General**: Name it "NeoPhp Scheduler"
   - **Triggers**: Set to run every minute (repeat every 1 minute, indefinitely)
   - **Actions**: 
     - Program: `C:\path\to\php.exe`
     - Arguments: `neo schedule:run`
     - Start in: `C:\path\to\your\project`
3. Save and enable the task

### Docker

Add to your `docker-compose.yml`:

```yaml
services:
  scheduler:
    image: php:8.3-cli
    working_dir: /app
    volumes:
      - .:/app
    command: >
      sh -c "while true; do
        php neo schedule:run
        sleep 60
      done"
```

Or use a separate cron container:

```yaml
services:
  cron:
    image: php:8.3-cli
    working_dir: /app
    volumes:
      - .:/app
      - ./docker/cron/crontab:/etc/cron.d/scheduler
    command: cron -f
```

Create `docker/cron/crontab`:

```
* * * * * cd /app && php neo schedule:run >> /app/storage/logs/scheduler.log 2>&1
```

## CLI Commands

### schedule:run

Runs all scheduled tasks that are currently due:

```bash
php neo schedule:run
```

Output:
```
Running Scheduled Tasks
=======================

Found 3 task(s) to run.

┌────────┬─────────────────┬─────────────────────┬──────────┬─────────┐
│ Status │ Task            │ Started At          │ Duration │ Result  │
├────────┼─────────────────┼─────────────────────┼──────────┼─────────┤
│ ✓      │ logs:clear      │ 2024-01-15 10:00:00 │ 0.25s    │ Success │
│ ✓      │ cache:clear     │ 2024-01-15 10:00:01 │ 0.18s    │ Success │
│ ✗      │ backup:database │ 2024-01-15 10:00:02 │ 1.45s    │ Failed  │
└────────┴─────────────────┴─────────────────────┴──────────┴─────────┘

Completed: 2 successful, 1 failed
```

### schedule:list

Lists all scheduled tasks and their configurations:

```bash
php neo schedule:list
```

Output:
```
Scheduled Tasks
===============

Total: 5 task(s)

┌───┬─────────────────┬─────────────┬─────────┬──────────┬─────────────────────┐
│ # │ Description     │ Expression  │ Due Now │ Will Run │ Next Run            │
├───┼─────────────────┼─────────────┼─────────┼──────────┼─────────────────────┤
│ 1 │ logs:clear      │ 0 0 * * *   │ No      │ No       │ 2024-01-16 00:00:00 │
│ 2 │ cache:clear     │ */5 * * * * │ Yes     │ Yes      │ 2024-01-15 10:05:00 │
│ 3 │ backup:database │ 0 2 * * 0   │ No      │ No       │ 2024-01-21 02:00:00 │
│ 4 │ send:emails     │ 0 9 * * 1-5 │ No      │ No       │ 2024-01-16 09:00:00 │
│ 5 │ Closure         │ 0 */2 * * * │ No      │ No       │ 2024-01-15 12:00:00 │
└───┴─────────────────┴─────────────┴─────────┴──────────┴─────────────────────┘

Cron Expression Format
======================

┌───────────── minute (0 - 59)
│ ┌───────────── hour (0 - 23)
│ │ ┌───────────── day of month (1 - 31)
│ │ │ ┌───────────── month (1 - 12)
│ │ │ │ ┌───────────── day of week (0 - 6)
│ │ │ │ │
* * * * *
```

## Examples

### Complete Schedule Definition

```php
use NeoPhp\Schedule\Scheduler;

$scheduler = new Scheduler('Asia/Bangkok');

// Clear old logs daily at midnight
$scheduler->command('logs:clear --days=30')
    ->daily()
    ->description('Clear logs older than 30 days')
    ->withoutOverlapping();

// Backup database every 6 hours
$scheduler->command('backup:database')
    ->cron('0 */6 * * *')
    ->description('Database backup')
    ->withoutOverlapping(360)
    ->runInBackground();

// Send daily reports on weekdays at 9 AM
$scheduler->call(function() {
    // Generate and send reports
    $report = generateDailyReport();
    sendEmail('admin@example.com', $report);
})
    ->weekdays()
    ->dailyAt('09:00')
    ->description('Send daily reports')
    ->environments(['production']);

// Clear cache every 5 minutes
$scheduler->command('cache:clear')
    ->everyFiveMinutes()
    ->description('Clear application cache');

// Monthly maintenance on the 1st at 3 AM
$scheduler->call(function() {
    performMonthlyMaintenance();
})
    ->monthlyOn(1, '03:00')
    ->description('Monthly maintenance')
    ->when(function() {
        return !isMaintenanceMode();
    });
```

### Testing Schedules

```php
use NeoPhp\Schedule\CronExpression;

// Test if expression is due
$cron = new CronExpression('*/5 * * * *');
$isDue = $cron->isDue(); // true if current minute is divisible by 5

// Get next run date
$nextRun = $cron->getNextRunDate();
echo $nextRun->format('Y-m-d H:i:s');

// Get human-readable description
echo $cron->getDescription(); // "every 5 minutes"
```

## Best Practices

1. **Always use descriptions**: Make it clear what each task does
2. **Prevent overlaps for long tasks**: Use `withoutOverlapping()` for tasks that might take a while
3. **Run heavy tasks in background**: Use `runInBackground()` for resource-intensive operations
4. **Set appropriate timezones**: Configure the correct timezone in your schedule config
5. **Monitor scheduled tasks**: Regularly check `schedule:list` to ensure tasks are configured correctly
6. **Log task outputs**: Redirect scheduler output to log files for debugging
7. **Test cron expressions**: Use online cron validators to verify your expressions
8. **Handle failures gracefully**: Wrap task code in try-catch blocks to prevent crashes

## Troubleshooting

### Tasks Not Running

1. Check system cron is set up correctly: `crontab -l`
2. Verify PHP path is correct in cron command
3. Check file permissions on project directory
4. Ensure timezone is set correctly in config
5. Check if task is actually due: `php neo schedule:list`

### Tasks Running Multiple Times

1. Make sure system cron is not set up multiple times
2. Use `withoutOverlapping()` to prevent duplicate execution
3. Check for multiple scheduler instances running

### Lock Files Not Clearing

```bash
# Clear all locks manually
rm storage/framework/schedule/*.lock

# Or clear expired locks (older than 24 hours)
find storage/framework/schedule -name "*.lock" -mtime +1 -delete
```

## Advanced Topics

### Custom Task Mutex

Create a custom mutex implementation:

```php
use NeoPhp\Schedule\TaskMutex;

class RedisTaskMutex extends TaskMutex
{
    protected $redis;
    
    public function create(string $key, int $expiresAt = 1440): bool
    {
        return $this->redis->set($key, time(), 'NX', 'EX', $expiresAt * 60);
    }
    
    public function forget(string $key): bool
    {
        return (bool) $this->redis->del($key);
    }
}
```

### Event Hooks

Add hooks before/after task execution:

```php
$scheduler->command('backup:database')
    ->daily()
    ->before(function() {
        // Code to run before task
        notify('Starting database backup');
    })
    ->after(function() {
        // Code to run after task
        notify('Database backup completed');
    })
    ->onSuccess(function() {
        notify('Backup successful');
    })
    ->onFailure(function() {
        notify('Backup failed');
    });
```

Note: These hooks would require extending the `ScheduledTask` class.
