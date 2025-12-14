# Query Builder

Powerful fluent interface for building database queries.

## Select Queries

### Basic Selects

```php
use NeoPhp\Database\DB;

// Select all columns
$users = DB::table('users')->get();

// Select specific columns
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->get();

// Select with alias
$users = DB::table('users')
    ->select('name', 'email as user_email')
    ->get();

// Add select
$query = DB::table('users')->select('name');
$users = $query->addSelect('email')->get();

// Distinct
$roles = DB::table('users')->distinct()->select('role')->get();
```

### Where Clauses

```php
// Basic where
$users = DB::table('users')
    ->where('active', true)
    ->get();

// Where with operator
$users = DB::table('users')
    ->where('votes', '>', 100)
    ->get();

// Multiple where (AND)
$users = DB::table('users')
    ->where('active', true)
    ->where('votes', '>', 100)
    ->get();

// Or where
$users = DB::table('users')
    ->where('votes', '>', 100)
    ->orWhere('name', 'John')
    ->get();

// Where between
$users = DB::table('users')
    ->whereBetween('votes', [1, 100])
    ->get();

// Where not between
$users = DB::table('users')
    ->whereNotBetween('votes', [1, 100])
    ->get();

// Where in
$users = DB::table('users')
    ->whereIn('id', [1, 2, 3])
    ->get();

// Where not in
$users = DB::table('users')
    ->whereNotIn('id', [1, 2, 3])
    ->get();

// Where null
$users = DB::table('users')
    ->whereNull('updated_at')
    ->get();

// Where not null
$users = DB::table('users')
    ->whereNotNull('updated_at')
    ->get();
```

### Advanced Where

```php
// Where date
$users = DB::table('users')
    ->whereDate('created_at', '2024-01-01')
    ->get();

// Where month
$users = DB::table('users')
    ->whereMonth('created_at', 12)
    ->get();

// Where year
$users = DB::table('users')
    ->whereYear('created_at', 2024)
    ->get();

// Where time
$users = DB::table('users')
    ->whereTime('created_at', '>', '12:00:00')
    ->get();

// Where column comparison
$users = DB::table('users')
    ->whereColumn('updated_at', '>', 'created_at')
    ->get();

// Where JSON
$users = DB::table('users')
    ->where('options->language', 'en')
    ->get();
```

### Conditional Where

```php
// When
$role = 'admin';
$users = DB::table('users')
    ->when($role, function ($query, $role) {
        return $query->where('role', $role);
    })
    ->get();

// Unless
$users = DB::table('users')
    ->unless(empty($role), function ($query) use ($role) {
        return $query->where('role', $role);
    })
    ->get();
```

### Where Exists

```php
// Where exists
$users = DB::table('users')
    ->whereExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('orders')
              ->whereColumn('orders.user_id', 'users.id');
    })
    ->get();

// Where not exists
$users = DB::table('users')
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('orders')
              ->whereColumn('orders.user_id', 'users.id');
    })
    ->get();
```

## Joins

### Inner Join

```php
$users = DB::table('users')
    ->join('contacts', 'users.id', '=', 'contacts.user_id')
    ->select('users.*', 'contacts.phone')
    ->get();

// Join with multiple conditions
$users = DB::table('users')
    ->join('contacts', function ($join) {
        $join->on('users.id', '=', 'contacts.user_id')
             ->where('contacts.active', '=', true);
    })
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

### Right Join

```php
$users = DB::table('users')
    ->rightJoin('posts', 'users.id', '=', 'posts.user_id')
    ->get();
```

### Cross Join

```php
$sizes = DB::table('sizes')
    ->crossJoin('colors')
    ->get();
```

## Ordering

### Order By

```php
// Order ascending
$users = DB::table('users')->orderBy('name')->get();

// Order descending
$users = DB::table('users')->orderBy('name', 'desc')->get();

// Multiple order
$users = DB::table('users')
    ->orderBy('name')
    ->orderBy('email')
    ->get();

// Latest/Oldest
$users = DB::table('users')->latest()->get();
$users = DB::table('users')->oldest()->get();

// Random order
$users = DB::table('users')->inRandomOrder()->get();

// Reorder
$query = DB::table('users')->orderBy('name');
$users = $query->reorder('email', 'desc')->get();
```

## Grouping

### Group By & Having

```php
// Group by
$users = DB::table('orders')
    ->groupBy('user_id')
    ->get();

// Group by with count
$stats = DB::table('orders')
    ->select('user_id', DB::raw('COUNT(*) as total'))
    ->groupBy('user_id')
    ->get();

// Having
$users = DB::table('orders')
    ->select('user_id', DB::raw('COUNT(*) as total'))
    ->groupBy('user_id')
    ->having('total', '>', 5)
    ->get();

// Having raw
$users = DB::table('orders')
    ->groupBy('user_id')
    ->havingRaw('SUM(amount) > 1000')
    ->get();
```

## Limit & Offset

### Skip & Take

```php
// Take (limit)
$users = DB::table('users')->take(5)->get();

// Skip (offset)
$users = DB::table('users')->skip(10)->get();

// Combined
$users = DB::table('users')
    ->skip(10)
    ->take(5)
    ->get();

// Limit & offset
$users = DB::table('users')
    ->offset(10)
    ->limit(5)
    ->get();

// For page
$page = 2;
$perPage = 10;
$users = DB::table('users')
    ->forPage($page, $perPage)
    ->get();
```

## Aggregates

### Count, Sum, Avg, Min, Max

```php
// Count
$count = DB::table('users')->count();
$count = DB::table('users')->where('active', true)->count();

// Sum
$total = DB::table('orders')->sum('amount');

// Average
$avg = DB::table('orders')->avg('amount');

// Min
$min = DB::table('orders')->min('amount');

// Max
$max = DB::table('orders')->max('amount');

// Multiple aggregates
$stats = DB::table('orders')
    ->select(
        DB::raw('COUNT(*) as count'),
        DB::raw('SUM(amount) as total'),
        DB::raw('AVG(amount) as average')
    )
    ->first();
```

## Insert

### Insert Records

```php
// Insert single
DB::table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// Insert and get ID
$id = DB::table('users')->insertGetId([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// Insert multiple
DB::table('users')->insert([
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
]);

// Insert or ignore
DB::table('users')->insertOrIgnore([
    'id' => 1,
    'name' => 'John',
]);
```

## Update

### Update Records

```php
// Update
DB::table('users')
    ->where('id', 1)
    ->update(['name' => 'John Doe']);

// Update or insert
DB::table('users')->updateOrInsert(
    ['email' => 'john@example.com'],
    ['name' => 'John', 'active' => true]
);

// Increment
DB::table('posts')->where('id', 1)->increment('views');
DB::table('posts')->where('id', 1)->increment('views', 5);

// Decrement
DB::table('posts')->where('id', 1)->decrement('views');
DB::table('posts')->where('id', 1)->decrement('views', 5);

// Increment with update
DB::table('posts')->where('id', 1)->increment('views', 1, [
    'updated_at' => now(),
]);
```

## Delete

### Delete Records

```php
// Delete where
DB::table('users')->where('id', 1)->delete();

// Delete multiple
DB::table('users')->whereIn('id', [1, 2, 3])->delete();

// Truncate
DB::table('users')->truncate();
```

## Upsert

### Insert or Update

```php
// Upsert
DB::table('flights')->upsert(
    [
        ['departure' => 'Oakland', 'destination' => 'San Diego', 'price' => 99],
        ['departure' => 'Chicago', 'destination' => 'New York', 'price' => 150],
    ],
    ['departure', 'destination'], // Unique columns
    ['price'] // Columns to update
);
```

## Unions

### Union Queries

```php
$first = DB::table('users')->whereNull('first_name');
$users = DB::table('users')
    ->whereNull('last_name')
    ->union($first)
    ->get();

// Union all
$users = DB::table('users')
    ->whereNull('last_name')
    ->unionAll($first)
    ->get();
```

## Subqueries

### Select Subquery

```php
$latestPosts = DB::table('posts')
    ->select('user_id', DB::raw('MAX(created_at) as last_post_created_at'))
    ->groupBy('user_id');

$users = DB::table('users')
    ->joinSub($latestPosts, 'latest_posts', function ($join) {
        $join->on('users.id', '=', 'latest_posts.user_id');
    })
    ->get();
```

### Where Subquery

```php
$users = DB::table('users')
    ->whereIn('id', function ($query) {
        $query->select('user_id')
              ->from('orders')
              ->where('status', 'completed');
    })
    ->get();
```

## Raw Expressions

### DB::raw()

```php
// Raw select
$users = DB::table('users')
    ->select(DB::raw('COUNT(*) as user_count, status'))
    ->where('status', '<>', 1)
    ->groupBy('status')
    ->get();

// Raw where
$users = DB::table('users')
    ->whereRaw('price > IF(state = "TX", ?, 100)', [200])
    ->get();

// Raw order
$users = DB::table('users')
    ->orderByRaw('updated_at - created_at DESC')
    ->get();

// Raw having
$orders = DB::table('orders')
    ->select('department', DB::raw('SUM(price) as total_sales'))
    ->groupBy('department')
    ->havingRaw('SUM(price) > ?', [2500])
    ->get();
```

## Pagination

### Simple Pagination

```php
$users = DB::table('users')->paginate(15);

foreach ($users as $user) {
    echo $user->name;
}

// Pagination links
echo $users->links();

// Custom per page
$users = DB::table('users')->paginate($perPage = 20);

// Simple paginate (previous/next only)
$users = DB::table('users')->simplePaginate(15);
```

## Chunking

### Process in Chunks

```php
DB::table('users')->orderBy('id')->chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});

// Stop chunking
DB::table('users')->orderBy('id')->chunk(100, function ($users) {
    // Return false to stop
    return false;
});

// Chunk by ID
DB::table('users')->chunkById(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

### Lazy Collections

```php
DB::table('users')->orderBy('id')->lazy()->each(function ($user) {
    // Process one user at a time
});

// Lazy by ID
DB::table('users')->lazyById()->each(function ($user) {
    //
});
```

## Locking

### Pessimistic Locking

```php
// Shared lock
DB::table('users')
    ->where('id', 1)
    ->sharedLock()
    ->get();

// Lock for update
DB::table('users')
    ->where('id', 1)
    ->lockForUpdate()
    ->get();
```

## Debugging

### Debug Queries

```php
// Dump query
DB::table('users')->where('votes', '>', 100)->dd();

// Dump and die
DB::table('users')->where('votes', '>', 100)->dump();

// To SQL
$sql = DB::table('users')->where('votes', '>', 100)->toSql();
echo $sql; // "select * from users where votes > ?"

// Get bindings
$bindings = DB::table('users')->where('votes', '>', 100)->getBindings();
print_r($bindings); // [100]
```

## Best Practices

1. **Use Bindings** - Never concatenate user input into queries
2. **Index Columns** - Add indexes to WHERE and JOIN columns
3. **Select Specific Columns** - Don't use SELECT * in production
4. **Use Chunks** - Process large datasets in chunks
5. **Avoid N+1** - Use joins or eager loading
6. **Use Transactions** - For multiple related queries
7. **Cache Results** - Cache expensive queries
8. **Use Pagination** - Don't load all records at once
9. **Optimize Joins** - Minimize number of joins
10. **Log Slow Queries** - Monitor and optimize slow queries

## See Also

- [Getting Started](getting-started.md)
- [Models](models.md)
- [Relationships](relationships.md)
- [Migrations](migrations.md)
