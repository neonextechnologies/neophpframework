# Database Seeding

Populate your database with test data.

## Creating Seeders

### Generate Seeder

```bash
php artisan make:seeder UserSeeder
php artisan make:seeder DatabaseSeeder
```

### Basic Seeder

```php
namespace Database\Seeders;

class UserSeeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
```

## Running Seeders

### Execute Seeders

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserSeeder

# Fresh and seed
php artisan migrate:fresh --seed
```

## Model Factories

### Define Factory

```php
namespace Database\Factories;

class UserFactory
{
    public static function make(array $attributes = []): User
    {
        $defaults = [
            'name' => 'Test User ' . rand(1000, 9999),
            'email' => 'user' . rand(1000, 9999) . '@example.com',
            'password' => Hash::make('password'),
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ];
        
        $user = new User();
        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $user->$key = $value;
        }
        
        return $user;
    }
    
    public static function create(array $attributes = []): User
    {
        $user = self::make($attributes);
        
        $em = app(EntityManager::class);
        $em->persist($user);
        $em->run();
        
        return $user;
    }
}
```

### Use Factory in Seeder

```php
class UserSeeder
{
    public function run(): void
    {
        // Create single user
        UserFactory::create(['role' => 'admin']);
        
        // Create multiple users
        for ($i = 0; $i < 50; $i++) {
            UserFactory::create();
        }
    }
}
```

## Calling Seeders

### DatabaseSeeder

```php
namespace Database\Seeders;

class DatabaseSeeder
{
    public function run(): void
    {
        (new UserSeeder())->run();
        (new PostSeeder())->run();
        (new CategorySeeder())->run();
    }
}
```

## Bulk Insertion

### Insert Multiple Records

```php
class PostSeeder
{
    public function run(): void
    {
        $posts = [];
        
        for ($i = 1; $i <= 100; $i++) {
            $posts[] = [
                'title' => "Post Title {$i}",
                'content' => "Post content {$i}",
                'user_id' => rand(1, 10),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('posts')->insert($posts);
    }
}
```

## Faker Data

### Using Faker

```php
use Faker\Factory as Faker;

class UserSeeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        for ($i = 0; $i < 50; $i++) {
            DB::table('users')->insert([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'bio' => $faker->paragraph,
                'created_at' => $faker->dateTimeBetween('-1 year'),
            ]);
        }
    }
}
```

## Best Practices

1. **Use Factories** - For reusable test data generation
2. **Bulk Insert** - Use batch inserts for performance
3. **Realistic Data** - Use Faker for realistic test data
4. **Idempotent** - Seeders should be safe to run multiple times
5. **Order Matters** - Seed in correct order (parent before child)
6. **Clear Before Seed** - Truncate tables before seeding
7. **Environment Check** - Never seed production data accidentally

## See Also

- [Migrations](migrations.md)
- [Models](models.md)
- [Testing](../testing/database.md)
