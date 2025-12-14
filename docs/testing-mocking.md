# Mocking

Create test doubles for isolated unit testing.

## Introduction

Mocking allows you to isolate code under test by replacing dependencies with test doubles.

## Mockery

### Installation

```bash
composer require --dev mockery/mockery
```

### Basic Mock

```php
use Mockery;
use App\Services\PaymentGateway;

public function test_payment_processing()
{
    // Create mock
    $gateway = Mockery::mock(PaymentGateway::class);
    
    // Set expectations
    $gateway->shouldReceive('charge')
        ->once()
        ->with(100, 'usd')
        ->andReturn(['status' => 'success', 'id' => 'ch_123']);
    
    // Use mock
    $service = new PaymentService($gateway);
    $result = $service->process(100);
    
    // Assert
    $this->assertEquals('success', $result['status']);
}

protected function tearDown(): void
{
    Mockery::close(); // Important: Clean up mocks
    parent::tearDown();
}
```

## Expectations

### shouldReceive()

```php
// Method will be called once
$mock->shouldReceive('method')->once();

// Method will be called twice
$mock->shouldReceive('method')->twice();

// Method will be called exactly N times
$mock->shouldReceive('method')->times(3);

// Method may be called any number of times
$mock->shouldReceive('method')->zeroOrMoreTimes();

// Method will never be called
$mock->shouldReceive('method')->never();

// Method will be called at least once
$mock->shouldReceive('method')->atLeast()->once();
```

### with()

```php
// Exact arguments
$mock->shouldReceive('charge')
    ->with(100, 'usd');

// Any arguments
$mock->shouldReceive('charge')
    ->withAnyArgs();

// No arguments
$mock->shouldReceive('refresh')
    ->withNoArgs();

// Argument matchers
$mock->shouldReceive('charge')
    ->with(Mockery::type('int'), 'usd');

$mock->shouldReceive('setUser')
    ->with(Mockery::on(function ($user) {
        return $user->isActive();
    }));
```

### andReturn()

```php
// Return value
$mock->shouldReceive('find')
    ->andReturn(new User(['id' => 1]));

// Return different values
$mock->shouldReceive('next')
    ->andReturn(1, 2, 3);

// Return self (for chaining)
$mock->shouldReceive('where')
    ->andReturnSelf();

// Throw exception
$mock->shouldReceive('charge')
    ->andThrow(new PaymentException('Card declined'));

// Return null
$mock->shouldReceive('method')
    ->andReturnNull();
```

## Partial Mocks

### Spy

```php
// Spy allows real method calls
$spy = Mockery::spy(PaymentGateway::class);

$service = new PaymentService($spy);
$service->process(100);

// Assert method was called
$spy->shouldHaveReceived('charge')
    ->once()
    ->with(100, 'usd');
```

### Partial Mock

```php
$mock = Mockery::mock(PaymentGateway::class)->makePartial();

// Mock specific method
$mock->shouldReceive('getApiKey')
    ->andReturn('test_key');

// Other methods use real implementation
$result = $mock->charge(100, 'usd');
```

## Mocking Facades

### Mock Facade

```php
use NeoPhp\Support\Facades\Cache;

public function test_cache_usage()
{
    Cache::shouldReceive('get')
        ->once()
        ->with('key')
        ->andReturn('value');
    
    $result = $this->service->getData('key');
    
    $this->assertEquals('value', $result);
}
```

### Mock Multiple Calls

```php
Cache::shouldReceive('has')
    ->once()
    ->with('users')
    ->andReturn(false);

Cache::shouldReceive('put')
    ->once()
    ->with('users', Mockery::type('array'), 3600);
```

## Mocking Events

### Mock Event Dispatcher

```php
use NeoPhp\Events\Dispatcher;
use App\Events\UserRegistered;

public function test_event_dispatched()
{
    Event::fake();
    
    $user = User::create([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
    
    Event::assertDispatched(UserRegistered::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
}
```

### Assert Event Not Dispatched

```php
Event::fake();

$this->service->doSomething();

Event::assertNotDispatched(UserDeleted::class);
```

## Mocking Queue

### Fake Queue

```php
use NeoPhp\Queue\Queue;
use App\Jobs\ProcessPayment;

public function test_job_dispatched()
{
    Queue::fake();
    
    $this->service->processOrder($order);
    
    Queue::assertPushed(ProcessPayment::class, function ($job) use ($order) {
        return $job->order->id === $order->id;
    });
}
```

### Assert Job Count

```php
Queue::fake();

$this->service->bulkProcess($orders);

Queue::assertPushed(ProcessPayment::class, 10);
```

## Mocking Mail

### Fake Mail

```php
use NeoPhp\Mail\Mail;
use App\Mail\WelcomeEmail;

public function test_email_sent()
{
    Mail::fake();
    
    $user = User::factory()->create();
    
    Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
        return $mail->user->id === $user->id;
    });
}
```

## Mocking Notifications

### Fake Notifications

```php
use NeoPhp\Notifications\Notification;
use App\Notifications\OrderShipped;

public function test_notification_sent()
{
    Notification::fake();
    
    $user = User::factory()->create();
    $user->notify(new OrderShipped($order));
    
    Notification::assertSentTo(
        $user,
        OrderShipped::class,
        function ($notification) use ($order) {
            return $notification->order->id === $order->id;
        }
    );
}
```

## Mocking Storage

### Fake Storage

```php
use NeoPhp\Storage\Storage;

public function test_file_upload()
{
    Storage::fake('public');
    
    $response = $this->post('/upload', [
        'file' => UploadedFile::fake()->image('photo.jpg'),
    ]);
    
    Storage::disk('public')->assertExists('photos/photo.jpg');
}
```

## Mocking HTTP Requests

### Fake HTTP Client

```php
use NeoPhp\Http\Client\Http;

public function test_api_call()
{
    Http::fake([
        'api.example.com/*' => Http::response(['data' => 'value'], 200),
    ]);
    
    $response = $this->service->fetchData();
    
    $this->assertEquals('value', $response['data']);
}
```

### Sequence Responses

```php
Http::fake([
    'api.example.com/*' => Http::sequence()
        ->push(['data' => 'first'], 200)
        ->push(['data' => 'second'], 200)
        ->pushStatus(404),
]);
```

## Mocking Time

### Travel in Time

```php
use NeoPhp\Support\Facades\Date;

public function test_expired_subscription()
{
    $user = User::factory()->create([
        'subscription_ends_at' => now()->addDays(30),
    ]);
    
    // Travel 31 days into future
    $this->travel(31)->days();
    
    $this->assertTrue($user->subscriptionExpired());
}

public function test_specific_date()
{
    $this->travelTo(now()->startOfYear());
    
    $this->assertEquals('2025-01-01', now()->toDateString());
}
```

## Custom Mocks

### Create Test Double

```php
class FakePaymentGateway implements PaymentGatewayInterface
{
    private array $charges = [];
    
    public function charge(int $amount, string $currency): array
    {
        $id = 'ch_' . uniqid();
        
        $this->charges[] = [
            'id' => $id,
            'amount' => $amount,
            'currency' => $currency,
        ];
        
        return ['status' => 'success', 'id' => $id];
    }
    
    public function totalCharges(): int
    {
        return array_sum(array_column($this->charges, 'amount'));
    }
    
    public function chargeCount(): int
    {
        return count($this->charges);
    }
}

// Usage in test
public function test_with_fake_gateway()
{
    $gateway = new FakePaymentGateway();
    $service = new PaymentService($gateway);
    
    $service->process(100);
    $service->process(200);
    
    $this->assertEquals(2, $gateway->chargeCount());
    $this->assertEquals(300, $gateway->totalCharges());
}
```

## Best Practices

1. **Isolation** - Mock external dependencies
2. **Expectations** - Set clear expectations
3. **Cleanup** - Always close Mockery mocks
4. **Minimal Mocking** - Don't over-mock
5. **Real Tests** - Balance mocks with integration tests
6. **Test Doubles** - Consider fake implementations
7. **Readable** - Make test intent clear

## See Also

- [Getting Started](getting-started.md)
- [Database Testing](database.md)
- [Mockery Documentation](http://docs.mockery.io/)
