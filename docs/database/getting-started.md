# Database - Getting Started

Complete guide to working with databases in NeoPhp Framework.

## Quick Start

### Configuration

```php
// config/database.php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'neophp'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
        
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'neophp'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],
    ],
];
```

### Environment Setup

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=neophp
DB_USERNAME=root
DB_PASSWORD=secret
```

## Database Connections

### Default Connection

```php
use NeoPhp\Database\DB;

// Using default connection
$users = DB::table('users')->get();
```

### Multiple Connections

```php
// Using specific connection
$mysqlUsers = DB::connection('mysql')->table('users')->get();
$pgsqlUsers = DB::connection('pgsql')->table('users')->get();

// In model
class User extends Model
{
    protected $connection = 'mysql';
}
```

### Connection Management

```php
// Get connection
$connection = DB::connection();

// Test connection
try {
    DB::connection()->getPdo();
    echo "Connected successfully";
} catch (\Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Disconnect
DB::disconnect('mysql');

// Reconnect
DB::reconnect('mysql');
```

## Query Builder

### Select Queries

```php
// Get all records
$users = DB::table('users')->get();

// Get first record
$user = DB::table('users')->where('id', 1)->first();

// Get specific columns
$users = DB::table('users')->select('name', 'email')->get();

// Where clauses
$users = DB::table('users')
    ->where('active', true)
    ->where('age', '>', 18)
    ->get();

// Or where
$users = DB::table('users')
    ->where('role', 'admin')
    ->orWhere('role', 'editor')
    ->get();

// Where in
$users = DB::table('users')
    ->whereIn('id', [1, 2, 3])
    ->get();

// Where null
$users = DB::table('users')
    ->whereNull('deleted_at')
    ->get();
```

### Insert Queries

```php
// Insert single record
DB::table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('secret'),
]);

// Insert and get ID
$id = DB::table('users')->insertGetId([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
]);

// Insert multiple records
DB::table('users')->insert([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
]);
```

### Update Queries

```php
// Update records
DB::table('users')
    ->where('id', 1)
    ->update(['name' => 'Updated Name']);

// Update or insert
DB::table('users')->updateOrInsert(
    ['email' => 'john@example.com'],
    ['name' => 'John Doe', 'active' => true]
);

// Increment/Decrement
DB::table('posts')->where('id', 1)->increment('views');
DB::table('posts')->where('id', 1)->decrement('likes', 5);
```

### Delete Queries

```php
// Delete records
DB::table('users')->where('id', 1)->delete();

// Delete all records
DB::table('users')->delete();

// Truncate table
DB::table('users')->truncate();
```

## Raw Queries

### Execute Raw SQL

```php
// Select query
$users = DB::select('SELECT * FROM users WHERE active = ?', [true]);

// Insert query
DB::insert('INSERT INTO users (name, email) VALUES (?, ?)', [
    'John Doe',
    'john@example.com'
]);

// Update query
DB::update('UPDATE users SET active = ? WHERE id = ?', [false, 1]);

// Delete query
DB::delete('DELETE FROM users WHERE id = ?', [1]);

// General statement
DB::statement('DROP TABLE IF EXISTS temp_table');
```

### Raw Expressions

```php
// Use raw SQL in query builder
$users = DB::table('users')
    ->select(DB::raw('COUNT(*) as total'))
    ->where('active', true)
    ->get();

// Raw where
DB::table('posts')
    ->whereRaw('views > clicks * 2')
    ->get();
```

## Transactions

### Basic Transactions

```php
use NeoPhp\Database\DB;

DB::transaction(function () {
    DB::table('users')->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    DB::table('profiles')->insert([
        'user_id' => DB::getPdo()->lastInsertId(),
        'bio' => 'Developer',
    ]);
});
```

### Manual Transactions

```php
DB::beginTransaction();

try {
    // Database operations
    DB::table('accounts')->where('id', 1)->decrement('balance', 100);
    DB::table('accounts')->where('id', 2)->increment('balance', 100);
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### Nested Transactions

```php
DB::transaction(function () {
    DB::table('orders')->insert([...]);
    
    DB::transaction(function () {
        DB::table('order_items')->insert([...]);
    });
});
```

## Joins

### Inner Join

```php
$users = DB::table('users')
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->select('users.*', 'profiles.bio')
    ->get();
```

### Left Join

```php
$users = DB::table('users')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->select('users.name', DB::raw('COUNT(posts.id) as post_count'))
    ->groupBy('users.id')
    ->get();
```

### Cross Join

```php
$combinations = DB::table('sizes')
    ->crossJoin('colors')
    ->get();
```

## Aggregates

### Count, Sum, Avg

```php
// Count records
$count = DB::table('users')->count();

// Sum column
$total = DB::table('orders')->sum('amount');

// Average
$avg = DB::table('products')->avg('price');

// Min/Max
$min = DB::table('products')->min('price');
$max = DB::table('products')->max('price');
```

## Ordering & Limiting

### Order By

```php
// Order ascending
$users = DB::table('users')->orderBy('name')->get();

// Order descending
$users = DB::table('users')->orderBy('created_at', 'desc')->get();

// Multiple orders
$users = DB::table('users')
    ->orderBy('role')
    ->orderBy('name')
    ->get();

// Random order
$users = DB::table('users')->inRandomOrder()->get();
```

### Limit & Offset

```php
// Limit results
$users = DB::table('users')->limit(10)->get();

// Offset
$users = DB::table('users')->skip(10)->take(10)->get();

// Pagination
$page = 2;
$perPage = 10;
$users = DB::table('users')
    ->offset(($page - 1) * $perPage)
    ->limit($perPage)
    ->get();
```

## Grouping

### Group By

```php
$stats = DB::table('orders')
    ->select('user_id', DB::raw('COUNT(*) as total'))
    ->groupBy('user_id')
    ->get();
```

### Having

```php
$users = DB::table('orders')
    ->select('user_id', DB::raw('COUNT(*) as total'))
    ->groupBy('user_id')
    ->having('total', '>', 5)
    ->get();
```

## Chunking

### Process Large Datasets

```php
// Process in chunks
DB::table('users')->orderBy('id')->chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});

// Stop chunking
DB::table('users')->orderBy('id')->chunk(100, function ($users) {
    if ($someCondition) {
        return false; // Stop processing
    }
});
```

### Lazy Loading

```php
DB::table('users')->orderBy('id')->lazy()->each(function ($user) {
    // Process user one by one
});
```

## Query Logging

### Enable Logging

```php
// Enable query log
DB::enableQueryLog();

// Run queries
$users = DB::table('users')->get();

// Get query log
$queries = DB::getQueryLog();

foreach ($queries as $query) {
    echo $query['query'] . "\n";
    print_r($query['bindings']);
    echo "Time: " . $query['time'] . "ms\n";
}
```

### Listen to Queries

```php
DB::listen(function ($query) {
    echo $query->sql . "\n";
    print_r($query->bindings);
    echo "Time: " . $query->time . "ms\n";
});
```

## Connection Pooling

### Pool Configuration

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'pool' => [
        'enabled' => true,
        'min_connections' => 5,
        'max_connections' => 20,
    ],
],
```

## Best Practices

1. **Use Query Builder** - Avoid raw SQL when possible
2. **Use Transactions** - For multiple related operations
3. **Index Properly** - Add indexes to frequently queried columns
4. **Avoid N+1** - Use eager loading
5. **Use Pagination** - Don't load all records at once
6. **Use Chunking** - For processing large datasets
7. **Log Queries** - Monitor slow queries
8. **Use Prepared Statements** - Prevent SQL injection
9. **Close Connections** - Don't leave connections open
10. **Optimize Joins** - Minimize number of joins

## See Also

- [Query Builder](query-builder.md)
- [Models](models.md)
- [Migrations](migrations.md)
- [Seeding](seeding.md)
- [Relationships](relationships.md)
