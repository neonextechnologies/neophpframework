# HTTP Testing

NeoPhp provides a fluent API for testing your HTTP endpoints.

## Basic HTTP Tests

### GET Requests

```php
namespace Tests\Feature;

use NeoPhp\Testing\TestCase;

class UserApiTest extends TestCase
{
    public function testCanGetUsers()
    {
        $response = $this->get('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email']
                ]
            ]);
    }

    public function testCanGetSingleUser()
    {
        $response = $this->get('/api/users/1');

        $response->assertOk()
            ->assertJson([
                'id' => 1,
                'name' => 'John Doe',
            ]);
    }
}
```

### POST Requests

```php
public function testCanCreateUser()
{
    $response = $this->post('/api/users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ])
        ->assertJsonMissing(['password']);
}
```

### PUT/PATCH Requests

```php
public function testCanUpdateUser()
{
    $response = $this->put('/api/users/1', [
        'name' => 'Updated Name',
    ]);

    $response->assertOk()
        ->assertJson([
            'id' => 1,
            'name' => 'Updated Name',
        ]);
}

public function testCanPatchUser()
{
    $response = $this->patch('/api/users/1', [
        'email' => 'newemail@example.com',
    ]);

    $response->assertOk();
}
```

### DELETE Requests

```php
public function testCanDeleteUser()
{
    $response = $this->delete('/api/users/1');

    $response->assertOk()
        ->assertJson([
            'message' => 'User deleted successfully'
        ]);
}
```

## Authentication

### Basic Authentication

```php
public function testWithBasicAuth()
{
    $response = $this->withBasicAuth('user', 'password')
        ->get('/api/profile');

    $response->assertOk();
}
```

### Bearer Token

```php
public function testWithBearerToken()
{
    $token = 'your-api-token';

    $response = $this->withToken($token)
        ->get('/api/dashboard');

    $response->assertOk();
}
```

### Acting As User

```php
public function testAuthenticatedUser()
{
    $user = User::find(1);

    $response = $this->actingAs($user)
        ->get('/api/profile');

    $response->assertOk()
        ->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
}
```

## Headers and Cookies

### Custom Headers

```php
public function testWithHeaders()
{
    $response = $this->withHeaders([
        'X-API-Key' => 'secret',
        'Accept' => 'application/json',
    ])->get('/api/data');

    $response->assertOk();
}
```

### Cookies

```php
public function testWithCookies()
{
    $response = $this->withCookie('session_id', 'abc123')
        ->get('/dashboard');

    $response->assertOk();
}
```

### JSON Requests

```php
public function testJsonRequest()
{
    $response = $this->asJson()
        ->post('/api/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

    $response->assertCreated();
}

// Or use postJson directly
$response = $this->postJson('/api/users', $data);
```

## Response Assertions

### Status Assertions

```php
$response->assertStatus(200);           // Exact status
$response->assertOk();                  // 200
$response->assertCreated();             // 201
$response->assertAccepted();            // 202
$response->assertNoContent();           // 204
$response->assertNotFound();            // 404
$response->assertUnauthorized();        // 401
$response->assertForbidden();           // 403
$response->assertUnprocessable();       // 422
$response->assertServerError();         // 500
```

### Success/Error Assertions

```php
$response->assertSuccessful();          // 2xx status
$response->assertRedirect();            // 3xx status
$response->assertClientError();         // 4xx status
$response->assertServerError();         // 5xx status
```

### JSON Assertions

```php
// Exact JSON match
$response->assertJson([
    'id' => 1,
    'name' => 'John Doe',
]);

// JSON structure
$response->assertJsonStructure([
    'data' => [
        '*' => ['id', 'name', 'email']
    ],
    'meta' => ['total', 'per_page'],
]);

// JSON path
$response->assertJsonPath('data.0.name', 'John Doe');

// JSON count
$response->assertJsonCount(10, 'data');

// JSON fragment
$response->assertJsonFragment([
    'name' => 'John Doe'
]);

// JSON missing
$response->assertJsonMissing([
    'password' => 'secret'
]);
```

### Header Assertions

```php
$response->assertHeader('Content-Type', 'application/json');
$response->assertHeaderMissing('X-Debug');
```

### Cookie Assertions

```php
$response->assertCookie('session_id');
$response->assertCookieMissing('temp_token');
$response->assertCookieExpired('old_session');
```

## Validation Testing

### Test Validation Errors

```php
public function testValidationErrors()
{
    $response = $this->post('/api/users', [
        'name' => '', // Required
        'email' => 'invalid-email', // Invalid format
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email']);
}

public function testSpecificValidationError()
{
    $response = $this->post('/api/users', [
        'email' => 'invalid',
    ]);

    $response->assertJsonValidationErrorFor('email');
}
```

## File Uploads

### Test File Upload

```php
use NeoPhp\Http\UploadedFile;

public function testFileUpload()
{
    $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);

    $response = $this->post('/api/upload', [
        'file' => $file,
    ]);

    $response->assertOk()
        ->assertJson([
            'path' => 'uploads/avatar.jpg'
        ]);
}

public function testMultipleFileUpload()
{
    $files = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.jpg'),
    ];

    $response = $this->post('/api/upload-multiple', [
        'files' => $files,
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'paths');
}
```

### Create Fake Files

```php
// Image
$file = UploadedFile::fake()->image('avatar.jpg', 600, 600);

// PDF
$file = UploadedFile::fake()->create('document.pdf', 1024); // 1MB

// With specific mime type
$file = UploadedFile::fake()->create('video.mp4', 5120, 'video/mp4');
```

## Database Testing

### Test Database Changes

```php
public function testUserCreation()
{
    $this->post('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
}

public function testUserDeletion()
{
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->delete("/api/users/{$user->id}");

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
}
```

### Soft Deletes

```php
public function testSoftDelete()
{
    $user = User::create(['name' => 'Test']);

    $this->delete("/api/users/{$user->id}");

    $this->assertSoftDeleted('users', [
        'id' => $user->id,
    ]);
}
```

## Test Setup and Teardown

### Setup

```php
class UserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        User::factory()->count(10)->create();
    }

    protected function tearDown(): void
    {
        // Clean up
        User::truncate();

        parent::tearDown();
    }
}
```

### Database Transactions

```php
use NeoPhp\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    // Tests run in transaction and rollback automatically
    public function testExample()
    {
        // Changes are rolled back after test
    }
}
```

### Refresh Database

```php
use NeoPhp\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // Database is migrated fresh before each test
    public function testExample()
    {
        // ...
    }
}
```

## Mocking

### Mock External APIs

```php
use NeoPhp\Http\Client;
use NeoPhp\Testing\Fakes\HttpFake;

public function testExternalApiCall()
{
    HttpFake::fake([
        'https://api.example.com/*' => HttpFake::response([
            'data' => 'test'
        ], 200),
    ]);

    $response = Client::get('https://api.example.com/endpoint');

    expect($response->json())->toBe(['data' => 'test']);
}
```

### Mock Services

```php
use Mockery;

public function testWithMockedService()
{
    $mock = Mockery::mock(UserService::class);
    $mock->shouldReceive('getUsers')
        ->once()
        ->andReturn([]);

    $this->app->instance(UserService::class, $mock);

    $response = $this->get('/api/users');
    $response->assertOk();
}
```

## Testing Examples

### Complete Test Example

```php
namespace Tests\Feature;

use App\Models\User;
use NeoPhp\Testing\TestCase;
use NeoPhp\Testing\RefreshDatabase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function testCanListUsers()
    {
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'created_at']
                ]
            ]);
    }

    public function testCanCreateUser()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/users', $data);

        $response->assertCreated()
            ->assertJson([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function testCannotCreateUserWithInvalidData()
    {
        $response = $this->postJson('/api/users', [
            'name' => '',
            'email' => 'invalid-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function testCanUpdateUser()
    {
        $user = User::factory()->create();

        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJson([
                'name' => 'Updated Name',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function testCanDeleteUser()
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function testRequiresAuthentication()
    {
        $response = $this->getJson('/api/profile');

        $response->assertUnauthorized();
    }

    public function testAuthenticatedUserCanAccessProfile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/profile');

        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }
}
```

## Running Tests

```bash
# Run all tests
php neo test

# Run specific test file
php neo test tests/Feature/UserApiTest.php

# Run specific test method
php neo test --filter testCanCreateUser

# Run with coverage
php neo test --coverage

# Run in parallel
php neo test --parallel
```

## Best Practices

1. **Test Important Flows** - Authentication, payments, critical business logic
2. **Use Factories** - Generate test data easily
3. **Test Validation** - Ensure validation rules work correctly
4. **Test Authorization** - Verify permission checks
5. **Use Transactions** - Keep tests isolated
6. **Mock External Services** - Don't call real APIs in tests
7. **Test Edge Cases** - Empty data, invalid input, boundary conditions
8. **Keep Tests Fast** - Use in-memory databases, mock when possible
9. **Write Descriptive Names** - Test method names should describe what they test
10. **Follow AAA Pattern** - Arrange, Act, Assert

## See Also

- [Database Testing](database.md)
- [Mocking](mocking.md)
- [Browser Testing](browser.md)
- [Testing Guide](getting-started.md)
