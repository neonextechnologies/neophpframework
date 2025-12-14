# Test Assertions

Comprehensive assertion methods for testing.

## Basic Assertions

### Equality

```php
// Equal (loose comparison)
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);

// Identical (strict comparison)
$this->assertSame($expected, $actual);
$this->assertNotSame($expected, $actual);
```

### Boolean

```php
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertNull($value);
$this->assertNotNull($value);
```

### Numeric

```php
$this->assertGreaterThan(10, $value);
$this->assertGreaterThanOrEqual(10, $value);
$this->assertLessThan(10, $value);
$this->assertLessThanOrEqual(10, $value);
```

## Array Assertions

### Array Content

```php
// Has key
$this->assertArrayHasKey('key', $array);
$this->assertArrayNotHasKey('key', $array);

// Contains value
$this->assertContains('value', $array);
$this->assertNotContains('value', $array);

// Count
$this->assertCount(5, $array);
$this->assertNotCount(5, $array);

// Empty
$this->assertEmpty($array);
$this->assertNotEmpty($array);
```

### Array Structure

```php
$array = [
    'user' => [
        'id' => 1,
        'name' => 'John',
        'email' => 'john@example.com',
    ],
];

$this->assertArrayHasKey('user', $array);
$this->assertArrayHasKey('id', $array['user']);
$this->assertEquals(1, $array['user']['id']);
```

## String Assertions

### String Content

```php
// Contains
$this->assertStringContainsString('needle', 'haystack with needle');
$this->assertStringNotContainsString('missing', 'haystack');

// Starts/Ends with
$this->assertStringStartsWith('Hello', 'Hello World');
$this->assertStringEndsWith('World', 'Hello World');

// Matches regex
$this->assertMatchesRegularExpression('/^[A-Z]/', 'Hello');
$this->assertDoesNotMatchRegularExpression('/^[0-9]/', 'Hello');

// Length
$this->assertStringMatchesFormat('%s@%s', 'email@example.com');
```

## Object Assertions

### Object Properties

```php
// Instance of
$this->assertInstanceOf(User::class, $object);
$this->assertNotInstanceOf(Admin::class, $object);

// Has property
$this->assertObjectHasProperty('name', $object);
$this->assertObjectNotHasProperty('missing', $object);

// Class exists
$this->assertClassHasAttribute('table', Model::class);
```

## File Assertions

### File System

```php
// File exists
$this->assertFileExists('/path/to/file.txt');
$this->assertFileDoesNotExist('/path/to/missing.txt');

// Directory exists
$this->assertDirectoryExists('/path/to/directory');
$this->assertDirectoryDoesNotExist('/path/to/missing');

// File readable/writable
$this->assertFileIsReadable('/path/to/file.txt');
$this->assertFileIsWritable('/path/to/file.txt');

// File equals
$this->assertFileEquals('/expected.txt', '/actual.txt');
```

## JSON Assertions

### JSON Structure

```php
$json = '{"name":"John","age":30}';

// Valid JSON
$this->assertJson($json);

// JSON equals
$this->assertJsonStringEqualsJsonString($expected, $actual);

// JSON contains
$this->assertJsonStringEqualsJsonFile('/path/to/expected.json', $json);
```

## HTTP Response Assertions

### Status Codes

```php
$response->assertStatus(200);
$response->assertOk(); // 200
$response->assertCreated(); // 201
$response->assertAccepted(); // 202
$response->assertNoContent(); // 204
$response->assertNotFound(); // 404
$response->assertForbidden(); // 403
$response->assertUnauthorized(); // 401
$response->assertUnprocessable(); // 422
$response->assertInternalServerError(); // 500
```

### Response Content

```php
// Contains text
$response->assertSee('Welcome');
$response->assertDontSee('Error');

// Contains text in order
$response->assertSeeInOrder(['First', 'Second', 'Third']);

// Contains text (without HTML escaping)
$response->assertSeeText('Welcome');
$response->assertDontSeeText('Error');
```

### Redirects

```php
$response->assertRedirect('/home');
$response->assertRedirectToRoute('dashboard');
$response->assertRedirectToSignedRoute('verify');
$response->assertNoContent();
```

### Headers

```php
$response->assertHeader('Content-Type', 'application/json');
$response->assertHeaderMissing('X-Custom-Header');
```

### Cookies

```php
$response->assertCookie('name');
$response->assertCookie('name', 'value');
$response->assertCookieExpired('name');
$response->assertCookieNotExpired('name');
$response->assertCookieMissing('name');
```

### JSON Response

```php
// Exact JSON
$response->assertExactJson([
    'name' => 'John',
    'age' => 30,
]);

// Contains JSON
$response->assertJson([
    'name' => 'John',
]);

// JSON structure
$response->assertJsonStructure([
    'data' => [
        'id',
        'name',
        'email',
    ],
]);

// JSON count
$response->assertJsonCount(10);
$response->assertJsonCount(10, 'data');

// JSON path
$response->assertJsonPath('user.name', 'John');
$response->assertJsonPath('data.0.id', 1);

// JSON missing
$response->assertJsonMissing(['password']);
```

## View Assertions

### View Content

```php
$response->assertViewIs('posts.index');
$response->assertViewHas('posts');
$response->assertViewHas('post', $post);
$response->assertViewHas('count', 10);
$response->assertViewMissing('error');

// Assert view has all
$response->assertViewHasAll([
    'posts',
    'categories',
    'user',
]);
```

## Session Assertions

### Session Data

```php
$response->assertSessionHas('key');
$response->assertSessionHas('key', 'value');
$response->assertSessionHasAll(['key1', 'key2']);
$response->assertSessionMissing('key');
```

### Session Errors

```php
$response->assertSessionHasErrors('email');
$response->assertSessionHasErrors(['email', 'password']);
$response->assertSessionHasNoErrors();
$response->assertSessionDoesntHaveErrors('email');
```

## Database Assertions

### Database Records

```php
// Has record
$this->assertDatabaseHas('users', [
    'email' => 'john@example.com',
]);

// Missing record
$this->assertDatabaseMissing('users', [
    'email' => 'deleted@example.com',
]);

// Count records
$this->assertDatabaseCount('users', 10);
```

### Soft Deletes

```php
$this->assertSoftDeleted('posts', [
    'id' => 1,
]);

$this->assertNotSoftDeleted('posts', [
    'id' => 2,
]);
```

### Model States

```php
$this->assertModelExists($user);
$this->assertModelMissing($user);
```

## Authentication Assertions

### Auth State

```php
$this->assertAuthenticated();
$this->assertAuthenticatedAs($user);
$this->assertGuest();

$response->assertAuthenticated();
$response->assertGuest();
```

## Exception Assertions

### Expect Exception

```php
$this->expectException(InvalidArgumentException::class);
$this->expectExceptionMessage('Invalid argument');
$this->expectExceptionCode(400);

// Code that throws exception
$this->service->doSomething();
```

## Custom Assertions

### Create Custom Assertion

```php
namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function assertValidEmail(string $email): void
    {
        $this->assertMatchesRegularExpression(
            '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
            $email,
            "'{$email}' is not a valid email address"
        );
    }
    
    protected function assertArrayContainsOnly(string $type, array $array): void
    {
        foreach ($array as $item) {
            $this->assertInstanceOf($type, $item);
        }
    }
}

// Usage
public function test_email_validation()
{
    $this->assertValidEmail('test@example.com');
}
```

## Assertion Messages

### Custom Messages

```php
// Add custom message to any assertion
$this->assertEquals(
    $expected,
    $actual,
    'The values should be equal'
);

$this->assertTrue(
    $user->isActive(),
    'User should be active but is not'
);
```

## Best Practices

1. **Specific Assertions** - Use most specific assertion
2. **Clear Messages** - Provide clear failure messages
3. **One Concept** - Test one concept per test
4. **Readable** - Make assertions readable
5. **Complete** - Assert all important conditions
6. **Precise** - Use strict assertions when appropriate
7. **Helpful** - Add custom assertions for common checks

## See Also

- [Getting Started](getting-started.md)
- [HTTP Tests](http-tests.md)
- [Database Testing](database.md)
- [PHPUnit Documentation](https://phpunit.de/assertions.html)
