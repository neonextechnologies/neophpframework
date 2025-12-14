# Caching

Improve your application's performance with NeoPhp's powerful caching system supporting multiple drivers.

## Configuration

Configure cache drivers in `config/cache.php`:

```php
return [
    'default' => env('CACHE_DRIVER', 'file'),

    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_CACHE_DB', 1),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'NeoPhp_cache'),
];
```

## Basic Usage

### Storing Items

```php
use NeoPhp\Cache\Cache;

// Store for specific duration (seconds)
Cache::put('key', 'value', 3600); // 1 hour

// Store forever
Cache::forever('key', 'value');

// Store if not exists
Cache::add('key', 'value', 3600);

// Store with expiration time
Cache::put('key', 'value', now()->addMinutes(10));
```

### Retrieving Items

```php
// Get item
$value = Cache::get('key');

// Get with default value
$value = Cache::get('key', 'default');

// Get with closure default
$value = Cache::get('key', function() {
    return 'computed value';
});

// Get and delete
$value = Cache::pull('key');
```

### Checking Existence

```php
// Check if key exists
if (Cache::has('key')) {
    // Key exists
}

// Check if key doesn't exist
if (Cache::missing('key')) {
    // Key doesn't exist
}
```

### Removing Items

```php
// Delete single item
Cache::forget('key');

// Delete multiple items
Cache::forget(['key1', 'key2', 'key3']);

// Clear all cache
Cache::flush();
```

## Advanced Caching

### Remember

Store and retrieve in one operation:

```php
$users = Cache::remember('users.all', 3600, function() {
    return User::all();
});

// Remember forever
$settings = Cache::rememberForever('settings', function() {
    return Settings::all();
});
```

### Increment/Decrement

```php
// Increment
Cache::increment('page_views');
Cache::increment('page_views', 5);

// Decrement
Cache::decrement('items_left');
Cache::decrement('items_left', 3);
```

### Atomic Locks

Prevent race conditions:

```php
$lock = Cache::lock('process_orders', 10); // 10 seconds

if ($lock->get()) {
    try {
        // Process orders
    } finally {
        $lock->release();
    }
}

// Or use callback
Cache::lock('process_orders')->get(function() {
    // Process orders
});

// Block and wait for lock
$lock->block(5, function() {
    // Wait up to 5 seconds for lock
});
```

## Cache Tags

Group related cache items (Redis & Memcached only):

```php
// Store with tags
Cache::tags(['users', 'posts'])->put('key', 'value', 3600);

// Retrieve with tags
$value = Cache::tags(['users'])->get('key');

// Flush specific tags
Cache::tags(['users'])->flush();

// Multiple tag operations
Cache::tags(['users', 'admins'])->remember('admin_users', 3600, function() {
    return User::where('role', 'admin')->get();
});
```

## Multiple Cache Stores

```php
// Use specific store
$value = Cache::store('redis')->get('key');

// Store on specific driver
Cache::store('memcached')->put('key', 'value', 3600);

// Chain operations
Cache::store('redis')
    ->tags(['users'])
    ->remember('users.active', 3600, function() {
        return User::where('status', 'active')->get();
    });
```

## Query Caching

### Cache Database Queries

```php
// Cache query results
$users = User::query()
    ->where('status', 'active')
    ->remember(3600)
    ->get();

// Cache with tags
$users = User::query()
    ->where('role', 'admin')
    ->cacheTags(['users', 'admins'])
    ->remember(3600)
    ->get();

// Cache forever
$settings = Setting::query()->rememberForever()->get();
```

### Model Caching

```php
namespace App\Models;

use NeoPhp\Database\Model;
use NeoPhp\Cache\Cacheable;

class User extends Model
{
    use Cacheable;

    protected $cacheFor = 3600; // Cache for 1 hour
    protected $cacheTags = ['users'];
}

// Automatically cached
$user = User::find(1); // Cached
$users = User::all(); // Cached
```

## View Caching

### Cache Rendered Views

```php
// Cache view output
$html = Cache::remember('home_page', 3600, function() {
    return view('home')->render();
});
```

### Fragment Caching in Templates

```latte
{* Latte template *}
{cache 'sidebar', expire => '1 hour'}
    <div class="sidebar">
        {foreach $posts as $post}
            <div>{$post->title}</div>
        {/foreach}
    </div>
{/cache}
```

## Response Caching

### HTTP Cache Headers

```php
public function show(int $id): Response
{
    $post = Post::findOrFail($id);
    
    return response()
        ->json($post)
        ->setCache([
            'max_age' => 3600,
            'public' => true,
        ]);
}

// Or use Cache-Control directly
return response()
    ->json($data)
    ->header('Cache-Control', 'public, max-age=3600');
```

### ETags

```php
public function show(int $id): Response
{
    $post = Post::findOrFail($id);
    $etag = md5(json_encode($post));
    
    if ($request->header('If-None-Match') === $etag) {
        return response('', 304);
    }
    
    return response()
        ->json($post)
        ->header('ETag', $etag);
}
```

## Cache Events

### Listen to Cache Events

```php
use NeoPhp\Cache\Events\CacheHit;
use NeoPhp\Cache\Events\CacheMissed;
use NeoPhp\Cache\Events\KeyWritten;
use NeoPhp\Cache\Events\KeyForgotten;

Event::listen(CacheHit::class, function($event) {
    // Log cache hit
    logger()->debug('Cache hit', ['key' => $event->key]);
});

Event::listen(CacheMissed::class, function($event) {
    // Log cache miss
    logger()->debug('Cache miss', ['key' => $event->key]);
});
```

## Redis Specific

### Redis Commands

```php
$redis = Cache::store('redis')->getRedis();

// Set with expiration
$redis->setex('key', 3600, 'value');

// Get multiple keys
$values = $redis->mget(['key1', 'key2', 'key3']);

// Lists
$redis->lpush('queue', 'item');
$item = $redis->rpop('queue');

// Sets
$redis->sadd('tags', 'php', 'laravel');
$members = $redis->smembers('tags');

// Sorted sets
$redis->zadd('leaderboard', 100, 'user1');
$top = $redis->zrevrange('leaderboard', 0, 9);
```

### Redis Pub/Sub

```php
// Publish message
Cache::store('redis')->publish('channel', 'message');

// Subscribe to channel
Cache::store('redis')->subscribe(['channel'], function($message) {
    echo "Received: " . $message;
});
```

## Performance Optimization

### Cache Warming

```php
// Warm cache on deployment
public function warmCache(): void
{
    Cache::remember('users.all', 3600, function() {
        return User::all();
    });
    
    Cache::remember('categories', 3600, function() {
        return Category::all();
    });
    
    Cache::remember('popular_posts', 3600, function() {
        return Post::orderBy('views', 'desc')->limit(10)->get();
    });
}
```

### Cache Layers

```php
// Two-level cache (memory + redis)
public function get($key, $callback)
{
    // Try memory first
    $value = Cache::store('array')->get($key);
    if ($value !== null) {
        return $value;
    }
    
    // Try Redis
    $value = Cache::store('redis')->get($key);
    if ($value !== null) {
        Cache::store('array')->put($key, $value, 60);
        return $value;
    }
    
    // Compute and cache
    $value = $callback();
    Cache::store('redis')->put($key, $value, 3600);
    Cache::store('array')->put($key, $value, 60);
    
    return $value;
}
```

## Cache Busting

### Clear Specific Caches

```php
// Clear user cache on update
public function update(Request $request, int $id): Response
{
    $user = User::findOrFail($id);
    $user->update($request->validated());
    
    // Clear caches
    Cache::forget("user.{$id}");
    Cache::tags(['users'])->flush();
    
    return response()->json($user);
}
```

### Automatic Cache Busting

```php
namespace App\Models;

use NeoPhp\Database\Model;

class User extends Model
{
    protected static function booted()
    {
        // Clear cache on create
        static::created(function($user) {
            Cache::tags(['users'])->flush();
        });
        
        // Clear cache on update
        static::updated(function($user) {
            Cache::forget("user.{$user->id}");
            Cache::tags(['users'])->flush();
        });
        
        // Clear cache on delete
        static::deleted(function($user) {
            Cache::forget("user.{$user->id}");
            Cache::tags(['users'])->flush();
        });
    }
}
```

## Testing with Cache

```php
use NeoPhp\Testing\TestCase;

class UserTest extends TestCase
{
    public function testUserCache()
    {
        // Fake cache driver
        Cache::fake();
        
        // Test code
        $users = User::all();
        
        // Assert cache was set
        Cache::assertPut('users.all');
    }
}
```

## Best Practices

1. **Use Appropriate TTL** - Don't cache too long or too short
2. **Use Tags** - Group related cache items for easier management
3. **Cache Expensive Operations** - Database queries, API calls, computations
4. **Implement Cache Warming** - Preload frequently accessed data
5. **Clear Cache Appropriately** - Invalidate when data changes
6. **Use Redis for Production** - Better performance than file cache
7. **Monitor Cache Hit Ratio** - Track cache effectiveness
8. **Use Atomic Locks** - Prevent cache stampede
9. **Set Reasonable Sizes** - Don't cache huge objects
10. **Use Multiple Stores** - Different data, different strategies

## CLI Commands

```bash
# Clear all cache
php neo cache:clear

# Clear specific store
php neo cache:clear --store=redis

# Clear tagged cache
php neo cache:clear --tags=users,posts

# Show cache statistics
php neo cache:stats
```

## See Also

- [Redis](redis.md)
- [Memcached](memcached.md)
- [Performance Optimization](../deployment/optimization.md)
- [Query Optimization](query-optimization.md)
