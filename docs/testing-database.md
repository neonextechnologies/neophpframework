# Database Testing

Test database interactions and queries.

## Setup

### Test Database Configuration

```php
// phpunit.xml
<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

### Migrations

```php
namespace Tests\TestCase;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase; // Migrates database before each test
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }
}
```

## Database Traits

### RefreshDatabase

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_creation()
    {
        $user = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }
}
```

### DatabaseMigrations

```php
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PostTest extends TestCase
{
    use DatabaseMigrations; // Runs migrations before each test
    
    public function test_post_creation()
    {
        Post::create(['title' => 'Test']);
        $this->assertDatabaseCount('posts', 1);
    }
}
```

### DatabaseTransactions

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OrderTest extends TestCase
{
    use DatabaseTransactions; // Wraps test in transaction
    
    public function test_order_creation()
    {
        $order = Order::create(['total' => 100]);
        $this->assertDatabaseHas('orders', ['total' => 100]);
    }
    // Automatically rolled back after test
}
```

## Factories

### Define Factory

```php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;
    
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ];
    }
    
    public function unverified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
    
    public function admin(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'admin',
            ];
        });
    }
}
```

### Use Factory

```php
// Create single user
$user = User::factory()->create();

// Create multiple users
$users = User::factory()->count(3)->create();

// With attributes
$user = User::factory()->create([
    'name' => 'John',
]);

// With state
$admin = User::factory()->admin()->create();
$unverified = User::factory()->unverified()->create();

// Make (don't save to database)
$user = User::factory()->make();

// With relationships
$user = User::factory()
    ->has(Post::factory()->count(3))
    ->create();

// Alternative relationship syntax
$user = User::factory()
    ->hasPosts(3)
    ->create();
```

## Seeders

### Define Seeder

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);
        
        // Create regular users with posts
        User::factory()
            ->count(10)
            ->has(Post::factory()->count(3))
            ->create();
    }
}
```

### Use Seeder in Tests

```php
public function test_with_seeded_data()
{
    $this->seed(); // Run DatabaseSeeder
    
    $this->assertDatabaseCount('users', 11);
    $this->assertDatabaseCount('posts', 30);
}

// Specific seeder
public function test_with_specific_seeder()
{
    $this->seed(UserSeeder::class);
}
```

## Database Assertions

### assertDatabaseHas()

```php
public function test_user_creation()
{
    User::create([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
    
    $this->assertDatabaseHas('users', [
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
}
```

### assertDatabaseMissing()

```php
public function test_user_deletion()
{
    $user = User::factory()->create();
    $user->delete();
    
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
}
```

### assertDatabaseCount()

```php
public function test_multiple_users()
{
    User::factory()->count(5)->create();
    
    $this->assertDatabaseCount('users', 5);
}
```

### assertSoftDeleted()

```php
public function test_soft_delete()
{
    $post = Post::factory()->create();
    $post->delete();
    
    $this->assertSoftDeleted('posts', [
        'id' => $post->id,
    ]);
}
```

### assertNotSoftDeleted()

```php
public function test_not_soft_deleted()
{
    $post = Post::factory()->create();
    
    $this->assertNotSoftDeleted('posts', [
        'id' => $post->id,
    ]);
}
```

## Query Testing

### Test Query Builder

```php
public function test_user_query()
{
    User::factory()->count(5)->create(['active' => true]);
    User::factory()->count(3)->create(['active' => false]);
    
    $activeUsers = User::where('active', true)->get();
    
    $this->assertCount(5, $activeUsers);
}
```

### Test Relationships

```php
public function test_user_has_posts()
{
    $user = User::factory()
        ->has(Post::factory()->count(3))
        ->create();
    
    $this->assertCount(3, $user->posts);
    $this->assertInstanceOf(Post::class, $user->posts->first());
}

public function test_post_belongs_to_user()
{
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    
    $this->assertEquals($user->id, $post->user->id);
}
```

## Testing Transactions

### Database Transactions

```php
public function test_transaction_rollback()
{
    $initialCount = User::count();
    
    try {
        DB::transaction(function () {
            User::create(['name' => 'John', 'email' => 'john@example.com']);
            throw new \Exception('Rollback');
        });
    } catch (\Exception $e) {
        // Transaction rolled back
    }
    
    $this->assertEquals($initialCount, User::count());
}
```

## Testing Query Performance

### Query Count

```php
public function test_n_plus_one_query()
{
    User::factory()->count(10)->create();
    
    DB::enableQueryLog();
    
    // Bad: N+1 queries
    $users = User::all();
    foreach ($users as $user) {
        $user->posts; // Additional query per user
    }
    
    $queries = DB::getQueryLog();
    $this->assertGreaterThan(10, count($queries));
    
    DB::flushQueryLog();
    
    // Good: Eager loading
    $users = User::with('posts')->get();
    foreach ($users as $user) {
        $user->posts; // No additional queries
    }
    
    $queries = DB::getQueryLog();
    $this->assertEquals(1, count($queries));
}
```

## Mock Database

### Mock Query Results

```php
use Mockery;

public function test_with_mocked_database()
{
    $mock = Mockery::mock(User::class);
    
    $mock->shouldReceive('find')
        ->once()
        ->with(1)
        ->andReturn(new User(['id' => 1, 'name' => 'John']));
    
    $this->app->instance(User::class, $mock);
    
    $user = User::find(1);
    $this->assertEquals('John', $user->name);
}
```

## Testing Migrations

### Test Migration

```php
public function test_users_table_migration()
{
    Schema::hasTable('users');
    
    $this->assertTrue(Schema::hasColumn('users', 'id'));
    $this->assertTrue(Schema::hasColumn('users', 'name'));
    $this->assertTrue(Schema::hasColumn('users', 'email'));
    $this->assertTrue(Schema::hasColumn('users', 'password'));
}
```

## Best Practices

1. **Isolation** - Use transactions or refresh database
2. **Factories** - Use factories instead of manual creation
3. **Relationships** - Test all relationship types
4. **Queries** - Test complex queries
5. **Performance** - Watch for N+1 queries
6. **Migrations** - Test migration up/down
7. **Seeders** - Use seeders for complex scenarios

## See Also

- [Getting Started](getting-started.md)
- [Mocking](mocking.md)
- [Models](../database/models.md)
- [Migrations](../database/migrations.md)
