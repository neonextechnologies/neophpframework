# Events System

Implement event-driven architecture in your application.

## Defining Events

### Create Event Class

```php
namespace App\Events;

class UserRegistered
{
    public function __construct(
        public User $user,
        public string $ipAddress
    ) {}
}
```

## Event Listeners

### Create Listener

```php
namespace App\Listeners;

use App\Events\UserRegistered;

class SendWelcomeEmail
{
    public function handle(UserRegistered $event): void
    {
        Mail::to($event->user->email)->send(
            new WelcomeEmail($event->user)
        );
    }
}
```

## Dispatching Events

### Fire Events

```php
use NeoPhp\Events\Event;
use App\Events\UserRegistered;

// Dispatch event
Event::dispatch(new UserRegistered($user, $request->ip()));

// Or using helper
event(new UserRegistered($user, $request->ip()));
```

## Registering Listeners

### Event Service Provider

```php
namespace App\Providers;

class EventServiceProvider
{
    protected $listen = [
        UserRegistered::class => [
            SendWelcomeEmail::class,
            LogRegistration::class,
            NotifyAdmin::class,
        ],
        
        OrderPlaced::class => [
            SendOrderConfirmation::class,
            UpdateInventory::class,
        ],
    ];
    
    public function boot(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, [new $listener(), 'handle']);
            }
        }
    }
}
```

## Closure Listeners

### Anonymous Listeners

```php
use App\Events\OrderPlaced;

Event::listen(OrderPlaced::class, function (OrderPlaced $event) {
    // Handle event
    Log::info('Order placed', ['order_id' => $event->order->id]);
});
```

## Queued Listeners

### Queue Event Listeners

```php
namespace App\Listeners;

use NeoPhp\Queue\ShouldQueue;

class SendWelcomeEmail implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        Mail::to($event->user->email)->send(
            new WelcomeEmail($event->user)
        );
    }
}
```

## Event Subscribers

### Create Subscriber

```php
namespace App\Listeners;

class UserEventSubscriber
{
    public function handleUserLogin($event): void
    {
        // Handle login
    }
    
    public function handleUserLogout($event): void
    {
        // Handle logout
    }
    
    public function subscribe($events): void
    {
        $events->listen(
            UserLoggedIn::class,
            [UserEventSubscriber::class, 'handleUserLogin']
        );
        
        $events->listen(
            UserLoggedOut::class,
            [UserEventSubscriber::class, 'handleUserLogout']
        );
    }
}
```

## Stopping Event Propagation

### Halt Event

```php
class ValidateOrder
{
    public function handle(OrderPlaced $event): bool
    {
        if (!$event->order->isValid()) {
            // Stop propagation
            return false;
        }
        
        return true;
    }
}
```

## Event Discovery

### Automatic Discovery

```php
// Automatically discover events in App\Events
Event::discover();

// Custom paths
Event::discover([
    app_path('Events'),
    app_path('Modules/*/Events'),
]);
```

## Testing Events

### Assert Events

```php
use NeoPhp\Testing\Facades\Event;

public function test_user_registration_sends_email()
{
    Event::fake();
    
    // Perform registration
    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);
    
    // Assert event was dispatched
    Event::assertDispatched(UserRegistered::class, function ($event) {
        return $event->user->email === 'john@example.com';
    });
    
    // Assert listener was called
    Event::assertListening(
        UserRegistered::class,
        SendWelcomeEmail::class
    );
}
```

## Best Practices

1. **Single Responsibility** - Each listener should have one job
2. **Immutable Events** - Don't modify event data
3. **Queue Heavy Tasks** - Queue time-consuming listeners
4. **Type Hint Events** - Always type hint event parameters
5. **Document Events** - Document all events and their listeners
6. **Error Handling** - Handle exceptions in listeners
7. **Event Naming** - Use descriptive past-tense names

## See Also

- [Queue System](queue.md)
- [Broadcasting](broadcasting.md)
- [Testing](../testing/getting-started.md)
