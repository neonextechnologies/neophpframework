# Getting Started with Testing

Write automated tests for your application.

## Introduction

NeoPhp Framework uses PHPUnit for testing with additional helpers for HTTP testing, database testing, and mocking.

## Installation

```bash
composer require --dev phpunit/phpunit
composer require --dev mockery/mockery
```

## Configuration

### phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

## Test Structure

### Test Organization

```
tests/
├── Feature/          # Feature tests (HTTP, integration)
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   └── RegisterTest.php
│   ├── PostTest.php
│   └── UserTest.php
├── Unit/             # Unit tests (isolated logic)
│   ├── Models/
│   │   └── UserTest.php
│   ├── Services/
│   │   └── PaymentServiceTest.php
│   └── Helpers/
│       └── StringHelperTest.php
└── TestCase.php      # Base test class
```

## Writing Tests

### Unit Test Example

```php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Calculator;

class CalculatorTest extends TestCase
{
    protected Calculator $calculator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new Calculator();
    }
    
    public function test_addition()
    {
        $result = $this->calculator->add(2, 3);
        $this->assertEquals(5, $result);
    }
    
    public function test_subtraction()
    {
        $result = $this->calculator->subtract(5, 3);
        $this->assertEquals(2, $result);
    }
    
    public function test_division_by_zero_throws_exception()
    {
        $this->expectException(\DivisionByZeroError::class);
        $this->calculator->divide(10, 0);
    }
}
```

### Feature Test Example

```php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class PostTest extends TestCase
{
    public function test_user_can_view_posts()
    {
        $response = $this->get('/posts');
        
        $response->assertStatus(200);
        $response->assertViewIs('posts.index');
        $response->assertViewHas('posts');
    }
    
    public function test_authenticated_user_can_create_post()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post('/posts', [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);
        
        $response->assertRedirect('/posts');
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
        ]);
    }
    
    public function test_guest_cannot_create_post()
    {
        $response = $this->post('/posts', [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);
        
        $response->assertRedirect('/login');
    }
}
```

## Assertions

### Common Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);
$this->assertSame($expected, $actual); // Strict comparison

// Truthiness
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertNull($value);
$this->assertNotNull($value);

// Arrays
$this->assertArrayHasKey('key', $array);
$this->assertContains($needle, $haystack);
$this->assertCount($expected, $array);
$this->assertEmpty($array);
$this->assertNotEmpty($array);

// Strings
$this->assertStringContainsString('needle', 'haystack');
$this->assertStringStartsWith('prefix', $string);
$this->assertStringEndsWith('suffix', $string);
$this->assertMatchesRegularExpression('/pattern/', $string);

// Objects
$this->assertInstanceOf(User::class, $object);
$this->assertObjectHasProperty('name', $object);

// Exceptions
$this->expectException(InvalidArgumentException::class);
$this->expectExceptionMessage('Invalid argument');
```

## HTTP Testing

### Making Requests

```php
// GET request
$response = $this->get('/posts');
$response = $this->get('/posts?page=2');

// POST request
$response = $this->post('/posts', [
    'title' => 'Test',
    'body' => 'Content',
]);

// PUT request
$response = $this->put('/posts/1', [
    'title' => 'Updated',
]);

// PATCH request
$response = $this->patch('/posts/1', [
    'title' => 'Updated',
]);

// DELETE request
$response = $this->delete('/posts/1');

// With headers
$response = $this->withHeaders([
    'Authorization' => 'Bearer ' . $token,
])->get('/api/posts');

// With cookies
$response = $this->withCookie('name', 'value')->get('/');
```

### Response Assertions

```php
// Status
$response->assertStatus(200);
$response->assertOk();
$response->assertCreated(); // 201
$response->assertNoContent(); // 204
$response->assertNotFound(); // 404
$response->assertForbidden(); // 403
$response->assertUnauthorized(); // 401

// Redirects
$response->assertRedirect('/posts');
$response->assertRedirectToRoute('posts.index');

// Views
$response->assertViewIs('posts.index');
$response->assertViewHas('posts');
$response->assertViewHas('post', $post);
$response->assertViewMissing('error');

// JSON
$response->assertJson(['name' => 'John']);
$response->assertJsonStructure([
    'data' => ['id', 'name', 'email'],
]);
$response->assertJsonCount(10, 'data');
$response->assertJsonPath('user.name', 'John');

// Headers
$response->assertHeader('Content-Type', 'application/json');
$response->assertHeaderMissing('X-Custom-Header');

// Cookies
$response->assertCookie('name');
$response->assertCookieExpired('name');
$response->assertCookieNotExpired('name');
```

## Database Testing

### Database Assertions

```php
// Record exists
$this->assertDatabaseHas('users', [
    'email' => 'john@example.com',
]);

// Record missing
$this->assertDatabaseMissing('users', [
    'email' => 'deleted@example.com',
]);

// Count records
$this->assertDatabaseCount('users', 5);

// Soft deletes
$this->assertSoftDeleted('posts', [
    'id' => 1,
]);
```

## Authentication Testing

### Acting as User

```php
$user = User::factory()->create();

// Authenticate for request
$response = $this->actingAs($user)->get('/dashboard');

// Assert authenticated
$this->assertAuthenticated();
$this->assertAuthenticatedAs($user);

// Assert guest
$this->assertGuest();
```

## Running Tests

### PHPUnit Commands

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature

# Run specific test file
vendor/bin/phpunit tests/Unit/UserTest.php

# Run specific test method
vendor/bin/phpunit --filter=test_user_can_login

# Generate code coverage
vendor/bin/phpunit --coverage-html coverage

# Stop on failure
vendor/bin/phpunit --stop-on-failure

# Parallel testing
vendor/bin/phpunit --parallel 4
```

## Test Doubles

### Mocking

```php
use Mockery;

public function test_payment_processing()
{
    $paymentGateway = Mockery::mock(PaymentGateway::class);
    
    $paymentGateway->shouldReceive('charge')
        ->once()
        ->with(100, 'usd')
        ->andReturn(['status' => 'success']);
    
    $service = new PaymentService($paymentGateway);
    $result = $service->processPayment(100, 'usd');
    
    $this->assertEquals('success', $result['status']);
}
```

## Best Practices

1. **Naming** - Use descriptive test method names
2. **AAA Pattern** - Arrange, Act, Assert
3. **Isolation** - Tests should be independent
4. **Coverage** - Aim for high code coverage
5. **Fast Tests** - Keep tests fast
6. **Clean Up** - Reset state between tests
7. **Real Scenarios** - Test real user scenarios

## See Also

- [HTTP Tests](http-tests.md)
- [Database Testing](database.md)
- [Mocking](mocking.md)
- [PHPUnit Documentation](https://phpunit.de/)
